<?php
// TEST STAGE: only a platform administrator may demote a company administrator.
// User, verification, approval, company membership and historical data remain intact.
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/platform_auth.php';
require_platform_admin($link);
require_platform_post();  // POST + platform CSRF protection

function remove_admin_go(string $key, string $message): never {
    header('Location: ' . BASE_URL . 'manage_company_admins.php?' . $key . '=' . rawurlencode($message));
    exit;
}

$userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
if (!$userId || $userId < 1) {
    remove_admin_go('error', 'Invalid user.');
}

// Read the user's current company before starting the transaction so the
// transactional count below cannot use a stale REPEATABLE READ snapshot.
$stmt = $link->prepare("SELECT company_id FROM users WHERE user_id = ? AND role = 'admin' LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$original = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$original || $original['company_id'] === null) {
    remove_admin_go('error', 'This account is not a company administrator.');
}
$companyId = (int) $original['company_id'];

try {
    $link->begin_transaction();

    // Serialise competing demotions within a company.
    $stmt = $link->prepare('SELECT company_id FROM companies WHERE company_id = ? FOR UPDATE');
    $stmt->bind_param('i', $companyId);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$company) {
        $link->rollback();
        remove_admin_go('error', 'Company not found.');
    }

    // Verify the account again while holding a row lock.
    $stmt = $link->prepare('SELECT role, company_id FROM users WHERE user_id = ? FOR UPDATE');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target || $target['role'] !== 'admin' || (int) $target['company_id'] !== $companyId) {
        $link->rollback();
        remove_admin_go('error', 'Account role or company changed. Refresh the page.');
    }

    // Prevent leaving a company without any administrator.
    $stmt = $link->prepare("SELECT COUNT(*) AS admin_count FROM users WHERE company_id = ? AND role = 'admin'");
    $stmt->bind_param('i', $companyId);
    $stmt->execute();
    $adminCount = (int) $stmt->get_result()->fetch_assoc()['admin_count'];
    $stmt->close();
    if ($adminCount <= 1) {
        $link->rollback();
        remove_admin_go('error', 'Cannot remove the last administrator of a company. Assign another admin first.');
    }

    // IMPORTANT: only role changes. Do not clear approved/is_verified/company_id.
    $stmt = $link->prepare(
        "UPDATE users SET role = 'user'
         WHERE user_id = ? AND company_id = ? AND role = 'admin'"
    );
    $stmt->bind_param('ii', $userId, $companyId);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();
    if ($changed !== 1) {
        $link->rollback();
        remove_admin_go('error', 'Administrator permissions could not be changed.');
    }
    $link->commit();
    remove_admin_go('msg', 'Administrator permissions removed. Ask the user to sign out and back in.');
} catch (mysqli_sql_exception $e) {
    $link->rollback();
    error_log('Remove company admin error: ' . $e->getMessage());
    remove_admin_go('error', 'Database error while removing administrator permissions.');
}
