
<?php
/** Update reagent details within the current company. */

require_once __DIR__ . '/../init.php';

// Check login
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// CSRF protection
$expectedToken = $_SESSION['csrf_token'] ?? null;
$submittedToken = $_POST['csrf_token'] ?? null;

if (
    !is_string($expectedToken) ||
    $expectedToken === '' ||
    !is_string($submittedToken) ||
    !hash_equals($expectedToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

// Database and company authorization
require BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

$companyId = tenant_require_user($link);
$userId = (int) $_SESSION['user_id'];

// Verify Company Admin role against database
$adminStmt = $link->prepare(
    "SELECT 1 FROM users
     WHERE user_id = ?
       AND company_id = ?
       AND role = 'admin'
     LIMIT 1"
);

$adminStmt->bind_param('ii', $userId, $companyId);
$adminStmt->execute();

$isAdmin = $adminStmt->get_result()->num_rows === 1;
$adminStmt->close();

if (!$isAdmin) {
    http_response_code(403);
    exit('Company Admin permission required.');
}

// Redirect helper
function returnToReagents(string $key, string $message): void {
    header(
        'Location: ' . BASE_URL .
        'admin/manage_reagents.php?' .
        $key . '=' . urlencode($message)
    );
    exit;
}

// Read a text field safely
function reagentTextField(string $field): ?string {
    $value = $_POST[$field] ?? '';
    return is_string($value) ? trim($value) : null;
}

// Read submitted values
$reagentId = filter_var(
    $_POST['reagent_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$name = reagentTextField('name');
$catalog = reagentTextField('catalog_number');
$category = reagentTextField('category');
$unit = reagentTextField('unit');
$storage = reagentTextField('storage_condition');

// Validate required fields
if (
    !is_int($reagentId) ||
    $name === null || $name === '' ||
    $catalog === null || $catalog === '' ||
    $unit === null || $unit === '' ||
    $category === null ||
    $storage === null
) {
    returnToReagents(
        'error',
        'Valid reagent ID, name, catalog number and unit are required.'
    );
}

// Optional fields
$category = $category === '' ? null : $category;
$storage = $storage === '' ? null : $storage;

try {

    // Only update reagents belonging to the current company
    $stmt = $link->prepare(
        'UPDATE reagents
         SET name = ?,
             catalog_number = ?,
             category = ?,
             unit = ?,
             storage_condition = ?
         WHERE reagent_id = ?
           AND company_id = ?
           AND is_deleted = 0'
    );

    $stmt->bind_param(
        'sssssii',
        $name,
        $catalog,
        $category,
        $unit,
        $storage,
        $reagentId,
        $companyId
    );

    $stmt->execute();

    $changed = $stmt->affected_rows;
    $stmt->close();

    // Zero affected rows can mean that no values changed.
    // Check whether the reagent still exists in this company.
    if ($changed === 0) {

        $checkStmt = $link->prepare(
            'SELECT 1 FROM reagents
             WHERE reagent_id = ?
               AND company_id = ?
               AND is_deleted = 0'
        );

        $checkStmt->bind_param(
            'ii',
            $reagentId,
            $companyId
        );

        $checkStmt->execute();

        $exists =
            $checkStmt->get_result()->num_rows === 1;

        $checkStmt->close();

        if (!$exists) {
            returnToReagents(
                'error',
                'Reagent not found in your company.'
            );
        }

        returnToReagents('msg', 'No changes needed.');
    }

    returnToReagents('msg', 'Reagent updated.');

} catch (mysqli_sql_exception $e) {

    error_log(
        'Reagently reagent update failed: ' .
        $e->getMessage()
    );

    returnToReagents(
        'error',
        (int) $e->getCode() === 1062
            ? 'A reagent with this catalog number already exists.'
            : 'Database error.'
    );
}
