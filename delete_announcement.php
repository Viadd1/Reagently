<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$announcement_id = (int)($_POST['announcement_id'] ?? 0);

if ($announcement_id <= 0) {
    header("Location: add_announcement.php?error=" . urlencode("No announcement selected."));
    exit;
}

$stmt = $link->prepare("DELETE FROM announcements WHERE announcement_id = ?");
$stmt->bind_param("i", $announcement_id);
$stmt->execute();

$deleted = $stmt->affected_rows;

$stmt->close();
$link->close();

if ($deleted > 0) {
    header("Location: add_announcement.php?msg=" . urlencode("Announcement deleted."));
} else {
    header("Location: add_announcement.php?error=" . urlencode("Announcement not found."));
}