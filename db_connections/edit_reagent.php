
<?php

// Start session and set base path
require_once __DIR__ . '/../init.php';

// Only allow POST requests from Manage Reagents
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Database connection
require_once BASE_PATH . '/db.php';

// Company authorization
require_once BASE_PATH . '/includes/tenant_auth.php';

$companyId = tenant_require_user($link);
$userId = (int) ($_SESSION['user_id'] ?? 0);

// Verify Company Admin permission using database
$adminStmt = $link->prepare(
    "SELECT 1
     FROM users
     WHERE user_id = ?
       AND company_id = ?
       AND role = 'admin'
     LIMIT 1"
);

$adminStmt->bind_param(
    'ii',
    $userId,
    $companyId
);

$adminStmt->execute();

$isAdmin = $adminStmt->get_result()->num_rows === 1;

$adminStmt->close();

if (!$isAdmin) {
    http_response_code(403);
    exit('Company Admin permission required.');
}

// CSRF protection for opening the edit form
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

// Validate reagent ID
$reagentId = filter_var(
    $_POST['reagent_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!is_int($reagentId)) {
    http_response_code(400);
    exit('Invalid reagent ID.');
}

// Retrieve reagent belonging to current company
$sql = "
SELECT
    name,
    catalog_number,
    category,
    unit,
    storage_condition

FROM reagents

WHERE reagent_id = ?
  AND company_id = ?
  AND is_deleted = 0
";

$stmt = $link->prepare($sql);

$stmt->bind_param(
    'ii',
    $reagentId,
    $companyId
);

$stmt->execute();

$reagent = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$reagent) {
    http_response_code(404);
    exit('Reagent not found in your company.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reagently - Edit Reagent</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>

<body>

<?php
include BASE_PATH . '/navbar.php';
?>

<main class="page">

    <h2>Edit Reagent</h2>

    <form
        action="<?= BASE_URL ?>db_connections/update_reagent.php"
        method="POST"
    >

        <!-- Reagent ID -->
        <input
            type="hidden"
            name="reagent_id"
            value="<?= $reagentId ?>"
        >

        <!-- CSRF Token -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <label>
            Name:
            <input
                type="text"
                name="name"
                required
                value="<?= htmlspecialchars($reagent['name'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>

        <br><br>

        <label>
            Catalog number:
            <input
                type="text"
                name="catalog_number"
                required
                value="<?= htmlspecialchars($reagent['catalog_number'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>

        <br><br>

        <label>
            Category:
            <input
                type="text"
                name="category"
                value="<?= htmlspecialchars($reagent['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>

        <br><br>

        <label>
            Unit:
            <input
                type="text"
                name="unit"
                required
                value="<?= htmlspecialchars($reagent['unit'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>

        <br><br>

        <label>
            Storage condition:
            <input
                type="text"
                name="storage_condition"
                value="<?= htmlspecialchars($reagent['storage_condition'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>

        <br><br>

        <input
            class="button"
            type="submit"
            value="Save changes"
        >

    </form>

    <br>

    <a
        class="button"
        href="<?= BASE_URL ?>admin/manage_reagents.php"
    >
        Cancel
    </a>

</main>

</body>
</html>

<?php
$link->close();
?>
