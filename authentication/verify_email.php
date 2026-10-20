<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
$token = $_GET['token'] ?? null;
if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
    http_response_code(400);
    exit('Invalid verification link.');
}
try {
    $stmt = $link->prepare(
        'UPDATE users SET is_verified = 1, verification_token = NULL, token_expires_at = NULL
         WHERE verification_token = ? AND token_expires_at > NOW() AND is_verified = 0'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $verified = $stmt->affected_rows === 1;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log('Email verification failed: ' . $e->getMessage());
    http_response_code(500); exit('Unable to verify email.');
}
$link->close();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Verify Email</title>
<link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head><body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page"><p><?= $verified ? 'Your email has been verified. You can log in once your company administrator approves your account.' : 'This verification link is invalid, expired or already used.' ?></p>
<a class="button" href="<?= BASE_URL ?>authentication/login.php">Login</a></main></body></html>
