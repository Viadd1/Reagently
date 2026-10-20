
<?php
// Start session and set base path
require_once __DIR__ . '/../init.php';

// Check login
if (empty($_SESSION['user_id'])) {
    header(
        'Location: ' . BASE_URL .
        'authentication/login.php'
    );
    exit;
}

// Database connection
require_once BASE_PATH . '/db.php';

// Company authorization
require_once BASE_PATH . '/includes/tenant_auth.php';

$companyId = tenant_require_user($link);
$userId = (int) $_SESSION['user_id'];

// Verify Company Admin permission
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

$isAdmin =
    $adminStmt->get_result()->num_rows === 1;

$adminStmt->close();

if (!$isAdmin) {
    http_response_code(403);
    exit('Company Admin permission required.');
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$link->close();
?>


<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Add Reagent</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>
<body>

<?php

// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

<h1>Add Reagent</h1>

<form
    action="<?= BASE_URL ?>db_connections/insert_reagent.php"
    method="POST"
>

<input
    type="hidden"
    name="csrf_token"
    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
>

    Name:
    <input
        type="text"
        name="name"
        required
    >

    <br><br>

    Catalog number:
    <input
        type="text"
        name="catalog_number"
        maxlength="100"
        required
    >

    <br><br>

    Category:
    <select name="category" id="category">
        <option value="">Select a category</option>
        <option value="organic">Organic</option>
        <option value="inorganic">Inorganic</option>
        <option value="biochemical">Biochemical</option>
        <option value="other">Other</option>
    </select>

    <br><br>

    Unit:
    <input
        type="text"
        name="unit"
        placeholder="mL, g, mg..."
        required
    >

    <br><br>

    Storage condition:
    <input
        type="text"
        name="storage_condition"
        placeholder="4°C, -20°C, room temp..."
    >

    <br><br>

    <input
        class="button"
        type="submit"
        value="Add Reagent"
    >

</form>

<br><br>

<a
    class="button"
    href="<?= BASE_URL ?>admin.php"
>
    Back to Admin
</a>

</main>
</body>
</html>