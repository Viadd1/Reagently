<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_require_post_csrf();
$title = $_POST['title'] ?? null;
$message = $_POST['message'] ?? null;
if (!is_string($title) || !is_string($message) || trim($title) === '' || trim($message) === '' || strlen(trim($title)) > 150) {
    header('Location: ' . BASE_URL . 'admin/add_announcement.php?error=' . urlencode('Valid title and message required.')); exit;
}
$title = trim($title); $message = trim($message);
$author = ($_SESSION['firstname'] ?? 'Admin') . ' (' . ($_SESSION['username'] ?? '') . ')';
try {
    $stmt = $link->prepare('INSERT INTO announcements (company_id, title, message, author_name) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $companyId, $title, $message, $author);
    $stmt->execute(); $stmt->close();
    $key = 'msg'; $notice = 'Announcement posted.';
} catch (mysqli_sql_exception $e) {
    error_log('Announcement insert failed: ' . $e->getMessage());
    $key = 'error'; $notice = 'Unable to post announcement.';
}
$link->close();
header('Location: ' . BASE_URL . 'admin/add_announcement.php?' . $key . '=' . urlencode($notice));
exit;
