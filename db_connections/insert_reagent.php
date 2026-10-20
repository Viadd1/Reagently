<?php
/** Insert a reagent into the logged-in administrator's company. */
require_once __DIR__ . '/../init.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// The add_reagent.php form must send this token.
$expectedToken = $_SESSION['csrf_token'] ?? null;
$submittedToken = $_POST['csrf_token'] ?? null;
if (
    !is_string($expectedToken) || $expectedToken === '' ||
    !is_string($submittedToken) ||
    !hash_equals($expectedToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

// Company ID comes from the authenticated session/database, not from POST.
$companyId = tenant_require_user($link);
$userId = (int) $_SESSION['user_id'];

$adminStmt = $link->prepare(
    "SELECT 1 FROM users
     WHERE user_id = ? AND company_id = ? AND role = 'admin'
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

// Reject array-valued form inputs; trim ordinary text values.
function reagentPostText(string $field): ?string
{
    $value = $_POST[$field] ?? '';
    return is_string($value) ? trim($value) : null;
}

$name = reagentPostText('name');
$catalogNumber = reagentPostText('catalog_number');
$category = reagentPostText('category');
$unit = reagentPostText('unit');
$storageCondition = reagentPostText('storage_condition');

$success = false;
$duplicate = false;
$message = '';

if (
    $name === null || $name === '' ||
    $catalogNumber === null || $catalogNumber === '' ||
    $unit === null || $unit === '' ||
    $category === null || $storageCondition === null
) {
    http_response_code(400);
    $message = 'Name, catalog number and unit are required.';
} else {
    // Empty optional fields may be saved as NULL.
    $category = $category === '' ? null : $category;
    $storageCondition = $storageCondition === '' ? null : $storageCondition;

    try {
        $stmt = $link->prepare(
            'INSERT INTO reagents
             (company_id, name, catalog_number, category, unit, storage_condition)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'isssss',
            $companyId,
            $name,
            $catalogNumber,
            $category,
            $unit,
            $storageCondition
        );
        $stmt->execute();
        $stmt->close();
        $success = true;
        $message = 'New reagent created successfully.';
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            $duplicate = true;
            $message = 'A reagent with this catalog number already exists.';
        } else {
            error_log('Reagently reagent insert failed: ' . $e->getMessage());
            $message = 'Database error. Reagent was not created.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Add Reagent</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL . 'style.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($success): ?>
        <a class="button" href="<?= htmlspecialchars(BASE_URL . 'admin/add_reagent.php', ENT_QUOTES, 'UTF-8') ?>">Add another reagent</a>
        <a class="button" href="<?= htmlspecialchars(BASE_URL . 'admin/add_lot.php', ENT_QUOTES, 'UTF-8') ?>">Add Reagent Lot</a>
    <?php elseif ($duplicate): ?>
        <p>If you want to add a new batch of the same reagent, please add a new reagent lot instead.</p>
        <a class="button" href="<?= htmlspecialchars(BASE_URL . 'admin/add_lot.php', ENT_QUOTES, 'UTF-8') ?>">Add Reagent Lot</a>
        <a class="button" href="<?= htmlspecialchars(BASE_URL . 'admin/add_reagent.php', ENT_QUOTES, 'UTF-8') ?>">Back to Add Reagent</a>
    <?php else: ?>
        <a class="button" href="<?= htmlspecialchars(BASE_URL . 'admin/add_reagent.php', ENT_QUOTES, 'UTF-8') ?>">Back to Add Reagent</a>
    <?php endif; ?>
</main>
</body>
</html>

<?php
// Close the connection after shared page components have rendered.
$link->close();
?>
