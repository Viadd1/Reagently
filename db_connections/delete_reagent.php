<!-- DB connection - Delete Reagent -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

include BASE_PATH . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$reagent_id = (int)$_POST['reagent_id'];

$stmt = $link->prepare("UPDATE reagents SET is_deleted = 1 WHERE reagent_id = ?");
$stmt->bind_param("i", $reagent_id);
$stmt->execute();

header("Location: /admin/manage_reagents.php?msg=" . urlencode("Reagent deleted."));
$stmt->close();
$link->close();