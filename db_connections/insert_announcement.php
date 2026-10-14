<!-- DB connections - Insert announcments -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Connect to database
include BASE_PATH . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$title   = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

// The author comes from the session, NOT from the form, so an admin can't post under someone else's name.
$author_name = ($_SESSION['firstname'] ?? 'Admin') . ' (' . ($_SESSION['username'] ?? '') . ')';

if ($title === '' || $message === '') {
    header("Location: /admin/add_announcement.php?error=" . urlencode("Title and message are required."));
    exit;
}

$stmt = $link->prepare(
    "INSERT INTO announcements (title, message, author_name) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $title, $message, $author_name);
$stmt->execute();

$stmt->close();
$link->close();

header("Location: /admin/add_announcement.php?msg=" . urlencode("Announcement posted."));