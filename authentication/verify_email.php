<?php
// Include database connection 
require_once __DIR__ . '/../init.php';
require BASE_PATH . '/db.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Invalid link.');
}

// Look up the token — must exist and not be expired
$sql = 'SELECT user_id FROM users
        WHERE verification_token = ?
        AND token_expires_at > NOW()
        AND is_verified = 0';

$stmt = $link->prepare($sql);
$stmt->bind_param('s', $token);
$stmt->execute();

$user = $stmt->fetch();
if (!$user) {
    die('This link is invalid or has expired. Please request a new one.');
}
$user_id = $user['user_id'];

$stmt->close();

// Mark the account as verified and clear the token
$verify_sql =  'UPDATE users
                SET is_verified = 1, verification_token = NULL
                WHERE user_id = ?';

$stmt = $link->prepare($verify_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();

echo 'Your email has been verified! <a href="login.php">Log in</a>';

$stmt->close();
$link->close();
?>