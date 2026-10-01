<?php
include 'db.php';

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
    <title>Reagently - Edit Reagent</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Reagently</h1></header>
<nav class="navbar">
    <a href="index.php">Home</a>
    <a href="admin.php">Admin</a>
    <a href="logout.php">Log out</a>
</nav>
<main class="page">
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

        <input type="submit" value="Save changes">
    </form>
    <a href="manage_reagents.php">Cancel</a>
</main>
</body>
</html>
<?php $stmt->close(); $link->close(); ?>