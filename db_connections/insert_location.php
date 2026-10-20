<?php
/**
 * Insert a storage location for the currently authenticated company.
 * Form: admin/add_location.php
 */
require_once __DIR__ . '/../init.php';

// The user must be signed in.
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

// This endpoint changes data, so GET requests are not allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Validate the CSRF token supplied by add_location.php.
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

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

// Derive the company from the authenticated user, never from $_POST.
$companyId = tenant_require_user($link);
$adminId = (int) $_SESSION['user_id'];

// Verify Company Admin privileges against the database.
$adminStmt = $link->prepare(
    "SELECT 1 FROM users
     WHERE user_id = ? AND company_id = ? AND role = 'admin'
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

// Safely read all four text fields; array submissions are invalid.
$fields = ['room', 'storage_unit', 'shelf', 'box_name'];
$values = [];
$validInput = true;
foreach ($fields as $field) {
    $raw = $_POST[$field] ?? '';
    if (!is_string($raw)) {
        $validInput = false;
        break;
    }
    $values[$field] = trim($raw);
    // All four columns have VARCHAR(100). Prefer UTF-8 character length.
    $length = function_exists('mb_strlen')
        ? mb_strlen($values[$field], 'UTF-8')
        : strlen($values[$field]);
    if ($length > 100) {
        $validInput = false;
        break;
    }
}

$isSuccess = false;
$message = '';

if (!$validInput || ($values['room'] ?? '') === '') {
    http_response_code(400);
    $message = 'Room is required, and each field must be at most 100 characters.';
} else {
    $room = $values['room'];
    $storageUnit = $values['storage_unit'] !== '' ? $values['storage_unit'] : null;
    $shelf = $values['shelf'] !== '' ? $values['shelf'] : null;
    $boxName = $values['box_name'] !== '' ? $values['box_name'] : null;

    try {
        // The company_id is supplied exclusively by the authenticated session.
        $stmt = $link->prepare(
            'INSERT INTO locations
             (company_id, room, storage_unit, shelf, box_name)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issss', $companyId, $room, $storageUnit, $shelf, $boxName);
        $stmt->execute();
        $isSuccess = $stmt->affected_rows === 1;
        $stmt->close();
        $message = $isSuccess
            ? 'New location created successfully.'
            : 'Could not create the location.';
    } catch (mysqli_sql_exception $e) {
        error_log('Reagently location insert failed: ' . $e->getMessage());
        http_response_code(500);
        $message = 'Database error. The location was not created.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Add Location</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL . 'style.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <br>
    <a class="button" href="<?= htmlspecialchars(BASE_URL . 'admin/add_location.php', ENT_QUOTES, 'UTF-8') ?>">
        Add another location
    </a>
    <a class="button" href="<?= htmlspecialchars(BASE_URL . 'admin.php', ENT_QUOTES, 'UTF-8') ?>">
        Back to Admin
    </a>
</main>
</body>
</html>
<?php
$link->close();
