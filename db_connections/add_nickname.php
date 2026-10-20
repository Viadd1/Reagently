
<?php

// Start session
require_once __DIR__ . '/../init.php';

// Existing admin guard and database connection
require_once BASE_PATH . '/db.php';

// Company authorization
require_once BASE_PATH . '/includes/tenant_auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify logged-in user and company
$companyId = tenant_require_user($link);
$adminId = (int) ($_SESSION['user_id'] ?? 0);

// Verify Company Admin role against database
$roleStmt = $link->prepare(
    "SELECT 1 FROM users
     WHERE user_id = ?
       AND company_id = ?
       AND role = 'admin'
     LIMIT 1"
);

$roleStmt->bind_param('ii', $adminId, $companyId);
$roleStmt->execute();

$isAdmin = $roleStmt->get_result()->num_rows === 1;
$roleStmt->close();

if (!$isAdmin) {
    http_response_code(403);
    exit('Company Admin permission required.');
}

// CSRF protection
$expectedToken = (string) ($_SESSION['csrf_token'] ?? '');
$submittedToken = $_POST['csrf_token'] ?? null;

if (
    $expectedToken === '' ||
    !is_string($submittedToken) ||
    !hash_equals($expectedToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

// Redirect helper
function backToManage(string $key, string $message): void {
    header(
        'Location: ' . BASE_URL .
        'admin/manage_reagents.php?' .
        $key . '=' . urlencode($message)
    );
    exit;
}

// Read and validate input
$reagentId = filter_var(
    $_POST['reagent_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$rawNickname = $_POST['nickname'] ?? '';
$nickname = is_string($rawNickname)
    ? trim($rawNickname)
    : '';

if (!is_int($reagentId)) {
    backToManage('error', 'Invalid reagent ID.');
}

if ($nickname === '') {
    backToManage('error', 'Nickname cannot be empty.');
}

// Insert nickname only if reagent belongs to this company
try {
    $stmt = $link->prepare(
        "INSERT INTO reagent_nicknames
            (reagent_id, nickname, created_by)

         SELECT r.reagent_id, ?, ?

         FROM reagents r

         WHERE r.reagent_id = ?
           AND r.company_id = ?
           AND r.is_deleted = 0"
    );

    $stmt->bind_param(
        'siii',
        $nickname,
        $adminId,
        $reagentId,
        $companyId
    );

    $stmt->execute();

    $inserted = $stmt->affected_rows;

    $stmt->close();

} catch (mysqli_sql_exception $e) {

    $message = ((int) $e->getCode() === 1062)
        ? 'This reagent already has that nickname.'
        : 'Database error.';

    backToManage('error', $message);
}

$link->close();

// No matching reagent in the current company
if ($inserted !== 1) {
    backToManage(
        'error',
        'Reagent not found in your company.'
    );
}

// Success
backToManage('msg', 'Nickname added.');