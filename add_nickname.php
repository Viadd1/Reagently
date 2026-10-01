<?php
include 'admin_guard.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$reagent_id = (int)$_POST['reagent_id'];
$nickname   = trim($_POST['nickname'] ?? '');
$admin_id   = $_SESSION['user_id'];

if ($nickname === '') {
    header("Location: manage_reagents.php?error=" . urlencode("Nickname cannot be empty."));
    exit;
}

$stmt = $link->prepare(
    "INSERT INTO reagent_nicknames (reagent_id, nickname, created_by) VALUES (?, ?, ?)");
$stmt->bind_param("isi", $reagent_id, $nickname, $admin_id);

try {
    $stmt->execute();
    header("Location: manage_reagents.php?msg=" . urlencode("Nickname added."));
} catch (mysqli_sql_exception $e) {
    $msg = ($e->getCode() == 1062)
        ? "This reagent already has that nickname."
        : "Database error.";
    header("Location: manage_reagents.php?error=" . urlencode($msg));
}
$stmt->close();
$link->close();