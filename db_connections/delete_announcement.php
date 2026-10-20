<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_require_post_csrf();
$id = filter_var($_POST['announcement_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!is_int($id)) { http_response_code(400); exit('Invalid announcement ID.'); }
try {
    $stmt = $link->prepare('DELETE FROM announcements WHERE announcement_id = ? AND company_id = ?');
    $stmt->bind_param('ii', $id, $companyId);
    $stmt->execute(); $deleted = $stmt->affected_rows;
    $stmt->close(); $key = $deleted === 1 ? 'msg' : 'error';
    $message = $deleted === 1 ? 'Announcement deleted.' : 'Announcement not found in your company.';
} catch (mysqli_sql_exception $e) {
    $key = 'error'; $message = 'Unable to delete announcement.';
}
$link->close();
header('Location: ' . BASE_URL . 'admin/add_announcement.php?' . $key . '=' . urlencode($message));
exit;
