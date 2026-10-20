
<?php

// Start session
require_once __DIR__ . '/../init.php';

// Only POST is allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Database and company authorization
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

$companyId = tenant_require_user($link);
$adminId = (int) ($_SESSION['user_id'] ?? 0);

// Verify Company Admin role
$adminStmt = $link->prepare(
    "SELECT 1 FROM users
     WHERE user_id = ?
       AND company_id = ?
       AND role = 'admin'
     LIMIT 1"
);

$adminStmt->bind_param('ii', $adminId, $companyId);
$adminStmt->execute();

$isAdmin = $adminStmt->get_result()->num_rows === 1;
$adminStmt->close();

if (!$isAdmin) {
    http_response_code(403);
    exit('Company Admin permission required.');
}

// CSRF protection
$expected = $_SESSION['csrf_token'] ?? null;
$submitted = $_POST['csrf_token'] ?? null;

if (
    !is_string($expected) ||
    $expected === '' ||
    !is_string($submitted) ||
    !hash_equals($expected, $submitted)
) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

// Read and validate reagent ID
$reagentId = filter_var(
    $_POST['reagent_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

// Redirect helper
function returnToManage(string $key, string $message): void
{
    header(
        'Location: ' . BASE_URL .
        'admin/manage_reagents.php?' .
        $key . '=' . urlencode($message)
    );
    exit;
}

if (!is_int($reagentId)) {
    returnToManage('error', 'Invalid reagent ID.');
}

// Soft delete:
// - Reagent must belong to the current company
// - Reagent must not already be deleted
// - Reagent must have no ACTIVE reservations

try {
    $stmt = $link->prepare(
        "UPDATE reagents AS r

         SET r.is_deleted = 1

         WHERE r.reagent_id = ?
           AND r.company_id = ?
           AND r.is_deleted = 0

           AND NOT EXISTS (
               SELECT 1
               FROM reagent_lots AS l
               JOIN reservations AS res
                   ON res.lot_id = l.lot_id
               WHERE l.reagent_id = r.reagent_id
                 AND res.status = 'ACTIVE'
           )"
    );

    $stmt->bind_param(
        'ii',
        $reagentId,
        $companyId
    );

    $stmt->execute();

    $changed = $stmt->affected_rows;
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    returnToManage('error', 'Database error.');
}

$link->close();

// The update did not match an eligible reagent
if ($changed !== 1) {
    returnToManage(
        'error',
        'Reagent not found in your company, already deleted, or has active reservations.'
    );
}

// Success
returnToManage('msg', 'Reagent deleted.');
