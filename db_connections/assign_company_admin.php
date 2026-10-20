<?php
// TEST STAGE: Platform Admin may promote an already-verified company user.
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/platform_auth.php';
require_platform_admin($link);
require_platform_post();

function assign_go(string $key, string $message): never {
    header('Location: ' . BASE_URL . 'manage_company_admins.php?' . $key . '=' . rawurlencode($message));
    exit;
}

$userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
if (!$userId || $userId < 1) assign_go('error', 'Invalid user.');

try {
    $link->begin_transaction();
    $stmt = $link->prepare(
        "SELECT u.user_id, u.role, u.company_id, u.is_verified, c.status
         FROM users u JOIN companies c ON c.company_id = u.company_id
         WHERE u.user_id = ? FOR UPDATE"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u || $u['role'] !== 'user' || (int)$u['is_verified'] !== 1 || $u['status'] !== 'ACTIVE') {
        $link->rollback();
        assign_go('error', 'Candidate must be a verified user of an ACTIVE company.');
    }
    // No company_id from POST: the user keeps their existing company membership.
    $companyId = (int) $u['company_id'];
    $stmt = $link->prepare(
        "UPDATE users SET role = 'admin', approved = 1
         WHERE user_id = ? AND company_id = ? AND role = 'user' AND is_verified = 1"
    );
    $stmt->bind_param('ii', $userId, $companyId);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();
    if ($changed !== 1) {
        $link->rollback();
        assign_go('error', 'User changed while assigning admin.');
    }
    $link->commit();
    assign_go('msg', 'Company administrator assigned. Ask them to sign in again.');
} catch (mysqli_sql_exception $e) {
    $link->rollback();
    assign_go('error', 'Database error.');
}
