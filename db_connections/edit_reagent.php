<!-- DB connection - Edit Reagent -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// Only admins can access this page
if (
    !isset($_SESSION['role'])
    ||
    $_SESSION['role'] !== 'admin'
) {
    die(
        "Access denied. Admin permission is required."
    );
}

include BASE_PATH . '/db.php';

$reagent_id = (int)($_POST['reagent_id'] ?? 0);
if (!$reagent_id) { die("No reagent ID provided."); }

$stmt = $link->prepare(
    "SELECT name, catalog_number, category, unit, storage_condition
     FROM reagents WHERE reagent_id = ? AND is_deleted = 0");
$stmt->bind_param("i", $reagent_id);
$stmt->execute();
$reagent = $stmt->get_result()->fetch_assoc();
if (!$reagent) { die("Reagent not found."); }
?>
<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Edit Reagent</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>

<?php

// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

    <h2>Edit Reagent</h2>
    <form action="update_reagent.php" method="POST">
        <input type="hidden" name="reagent_id" value="<?= $reagent_id ?>">

        <label>Name:
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($reagent['name']) ?>"></label><br><br>
        <label>Catalog number:
            <input type="text" name="catalog_number" required
                   value="<?= htmlspecialchars($reagent['catalog_number']) ?>"></label><br><br>
        <label>Category:
            <input type="text" name="category"
                   value="<?= htmlspecialchars($reagent['category'] ?? '') ?>"></label><br><br>
        <label>Unit:
            <input type="text" name="unit" required
                   value="<?= htmlspecialchars($reagent['unit']) ?>"></label><br><br>
        <label>Storage condition:
            <input type="text" name="storage_condition"
                   value="<?= htmlspecialchars($reagent['storage_condition'] ?? '') ?>"></label><br><br>

        <input class="button" type="submit" value="Save changes">
    </form>
    <a class="button" href="<?= BASE_URL ?>admin/manage_reagents.php">Cancel</a>

</main>
</body>
</html>

<?php $stmt->close(); $link->close(); ?>