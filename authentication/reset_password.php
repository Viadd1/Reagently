<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';

$token = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['token'] ?? null)
    : ($_GET['token'] ?? null);
if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
    http_response_code(400);
    exit('Invalid password reset link.');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? null;
    if (!is_string($password) || strlen($password) < 8) {
        http_response_code(400);
        exit('Password must be at least eight characters.');
    }
    try {
        $link->begin_transaction();
        $stmt = $link->prepare(
            'SELECT user_id FROM password_resets
             WHERE token = ? AND expires_at > NOW() AND used = 0
             FOR UPDATE'
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$record) {
            $link->rollback();
            http_response_code(409);
            exit('This password reset link is invalid, expired or already used.');
        }
        $userId = (int)$record['user_id'];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $link->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
        $stmt->close();
        $stmt = $link->prepare('UPDATE password_resets SET used = 1 WHERE token = ? AND used = 0');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();
        if ($updated !== 1) {
            throw new RuntimeException('Reset token changed before commit.');
        }
        $link->commit();
        $message = 'Password updated. You can now log in.';
    } catch (Throwable $e) {
        $link->rollback();
        error_log('Password reset failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Unable to reset password. No changes were saved.');
    }
}
$link->close();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reagently - Reset Password</title>
<link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head><body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page"><h2>Reset Password</h2>
<?php if ($message !== ''): ?>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<a class="button" href="<?= BASE_URL ?>authentication/login.php">Log in</a>
<?php else: ?>
<form method="POST" action="<?= BASE_URL ?>authentication/reset_password.php">
<input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
<label>New password <input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
<button class="button" type="submit">Set new password</button>
</form>
<?php endif; ?>
</main></body></html>
