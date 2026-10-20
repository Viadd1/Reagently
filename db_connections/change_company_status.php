<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/platform_auth.php';
require_platform_admin($link);
require_platform_post();

$id = filter_var($_POST['company_id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($_POST['new_status'] ?? '');
if (!$id || $id < 1 || !in_array($status, ['ACTIVE', 'SUSPENDED'], true)) {
    platform_go('error', 'Invalid company or status.');
}

try {
    $stmt = $link->prepare('UPDATE companies SET status = ? WHERE company_id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();
    platform_go($changed === 1 ? 'msg' : 'error', $changed === 1 ? 'Company status updated.' : 'Company not found or already in that status.');
} catch (mysqli_sql_exception $e) {
    platform_go('error', 'Database error.');
}
