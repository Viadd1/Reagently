<?php
/**
 * Add Storage Location form.
 * Only approved users with the current company's admin role may access it.
 * The company association is assigned by insert_location.php, not by this form.
 */

require_once __DIR__ . '/../init.php';

// Authentication
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

// Validate the current user's active company and access status.
$companyId = tenant_require_user($link);
$userId = (int) $_SESSION['user_id'];

// Never rely only on a role value stored in a session.
$adminStmt = $link->prepare(
    "SELECT 1
     FROM users
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
    $link->close();
    http_response_code(403);
    exit('Company Admin permission required.');
}

// Reuse a session token, or create one when it does not yet exist.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$link->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Add Location</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>

<main class="page">
    <h1>Add Storage Location</h1>

    <form action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>db_connections/insert_location.php" method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="room">Room:</label>
        <input id="room" type="text" name="room" maxlength="100" required>
        <br><br>

        <label for="storage_unit">Storage unit:</label>
        <input id="storage_unit" type="text" name="storage_unit" maxlength="100">
        <br><br>

        <label for="shelf">Shelf:</label>
        <input id="shelf" type="text" name="shelf" maxlength="100">
        <br><br>

        <label for="box_name">Box:</label>
        <input id="box_name" type="text" name="box_name" maxlength="100">
        <br><br>

        <input class="button" type="submit" value="Add Location">
    </form>

    <br>
    <a class="button" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>admin.php">Back to Admin</a>
</main>
</body>
</html>
