<?php
/** Soft account deactivation; never rewrite reservation/history owners to a fake user. */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId=tenant_require_user($link);
tenant_require_post_csrf();
$viewerId=(int)$_SESSION['user_id'];
$targetId=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!is_int($targetId)) {http_response_code(400);exit('Invalid user.');}
if ($targetId !== $viewerId && ($_SESSION['role']??'')!=='admin') {
    http_response_code(403);exit('Not permitted.');
}
try {
    // No destructive physical deletion. Prevent active reservations from being orphaned.
    $stmt=$link->prepare(
        "UPDATE users u SET u.approved=0
         WHERE u.user_id=? AND u.company_id=? AND u.role='user'
           AND NOT EXISTS (SELECT 1 FROM reservations res
                           WHERE res.user_id=u.user_id AND res.status='ACTIVE')"
    );
    $stmt->bind_param('ii',$targetId,$companyId);$stmt->execute();
    $changed=$stmt->affected_rows;$stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log('Deactivate user failed: '.$e->getMessage());
    http_response_code(500);exit('Could not deactivate user.');
}
$link->close();
if ($changed!==1) {http_response_code(409);exit('Only a regular user in your company without ACTIVE reservations can be deactivated.');}
if ($targetId === $viewerId) {
    $_SESSION=[];
    session_regenerate_id(true);
    header('Location: '.BASE_URL.'authentication/login.php'); exit;
}
header('Location: '.BASE_URL.'admin/view_users.php');exit;
