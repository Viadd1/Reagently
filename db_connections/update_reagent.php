<!-- DB Connection - Update Reagent -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

include BASE_PATH . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$reagent_id        = (int)$_POST['reagent_id'];
$name              = trim($_POST['name']);
$catalog_number    = trim($_POST['catalog_number']);
$category          = trim($_POST['category'] ?? '') ?: null;
$unit              = trim($_POST['unit']);
$storage_condition = trim($_POST['storage_condition'] ?? '') ?: null;

if ($name === '' || $catalog_number === '' || $unit === '') {
    header("Location: /admin/manage_reagents.php?error=" . urlencode("Name, catalog number and unit are required."));
    exit;
}

$stmt = $link->prepare(
    "UPDATE reagents
     SET name = ?, catalog_number = ?, category = ?, unit = ?, storage_condition = ?
     WHERE reagent_id = ? AND is_deleted = 0");
$stmt->bind_param("sssssi", $name, $catalog_number, $category, $unit, $storage_condition, $reagent_id);

try {
    $stmt->execute();
    header("Location: /admin/manage_reagents.php?msg=" . urlencode("Reagent updated."));
} catch (mysqli_sql_exception $e) {
    $msg = ($e->getCode() == 1062)
        ? "A reagent with this catalog number already exists."
        : "Database error.";
    header("Location: /admin/manage_reagents.php?error=" . urlencode($msg));
}
$stmt->close();
$link->close();