<?php
/** Public password-reset request with CSRF protection and neutral response. */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';

if (empty($_SESSION['forgot_csrf'])) {
    $_SESSION['forgot_csrf'] = bin2hex(random_bytes(32));
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expected = $_SESSION['forgot_csrf'];
    $provided = $_POST['csrf_token'] ?? null;
    if (!is_string($provided) || !hash_equals($expected, $provided)) {
        http_response_code(403); exit('Invalid request token. Please reload this page.');
    }
    $email = $_POST['email'] ?? null;
    if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        http_response_code(400); $message = 'Please enter a valid email address.';
    } else {
        $stmt = $link->prepare('SELECT user_id, username FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email); $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($account) {
            $token = bin2hex(random_bytes(32));
            try {
                $stmt = $link->prepare(
                    'INSERT INTO password_resets (user_id, token, expires_at)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                );
                $stmt->bind_param('is', $account['user_id'], $token);
                $stmt->execute(); $stmt->close();
                // In production set REAGENTLY_APP_URL=https://trusted.example
                // Never form a reset URL using an untrusted arbitrary Host header.
                $base = (string) (getenv('REAGENTLY_APP_URL') ?: '');
                if ($base === '') {
                    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
                    if (preg_match('/\A(?:localhost|127\.0\.0\.1)(?::[0-9]{1,5})?\z/D', $host)) {
                        $base = 'http://' . $host;
                    }
                }
                if ($base !== '' && preg_match('~\Ahttps?://[A-Za-z0-9.:-]+(?:/[^?#]*)?\z~D', $base)) {
                    $url = rtrim($base, '/') . BASE_URL . 'authentication/reset_password.php?token=' . rawurlencode($token);
                    try {
                        if (is_file(BASE_PATH . '/vendor/autoload.php') && is_file(BASE_PATH . '/.env')) {
                            require_once BASE_PATH . '/send_email.php';
                            $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                            $body = '<p>Reset your Reagently password within one hour:</p><p><a href="' . $safeUrl . '">Reset password</a></p><p>If you did not request this, ignore the message.</p>';
                            \Email\sendEmail($email, $account['username'], 'Reset your Reagently password', $body);
                        }
                    } catch (Throwable $e) {
                        error_log('Reset email failed: ' . $e->getMessage());
                    }
                } else {
                    error_log('Reset email not sent: missing trusted REAGENTLY_APP_URL / localhost origin.');
                }
            } catch (mysqli_sql_exception $e) {
                error_log('Password reset request failed: ' . $e->getMessage());
            }
        }
        $message = 'If an account exists for this email, a reset message will be sent when email delivery is configured.';
        // Rotate after a submission; this is not rate limiting.
        $_SESSION['forgot_csrf'] = bin2hex(random_bytes(32));
    }
}
$link->close();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reagently - Reset Password</title>
<link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head>
<body><?php include BASE_PATH . '/navbar.php'; ?><main class="page"><h2>Restore Password</h2>
<?php if ($message !== ''): ?><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<form method="POST" action="<?= BASE_URL ?>authentication/forgot_password.php">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['forgot_csrf'], ENT_QUOTES, 'UTF-8') ?>">
<label>Email <input type="email" name="email" maxlength="255" required></label>
<button class="button" type="submit">Send reset link</button>
</form>
<a class="button" href="<?= BASE_URL ?>authentication/login.php">Back to Login</a>
</main></body></html>
