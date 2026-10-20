<?php
// Company-aware login. No HTML output before redirect or session changes.
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required.');
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$captcha = strtoupper(trim((string) ($_POST['captcha'] ?? '')));
$expectedCaptcha = strtoupper((string) ($_SESSION['captcha_code'] ?? ''));
unset($_SESSION['captcha_code']);

function login_error(string $message): never {
    http_response_code(401);
    echo '<!DOCTYPE html><html lang="en"><meta charset="utf-8"><title>Login</title>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<a href="' . BASE_URL . 'authentication/login.php">Try again</a></html>';
    exit;
}

if ($captcha === '' || $expectedCaptcha === '' || !hash_equals($expectedCaptcha, $captcha)) {
    login_error('Incorrect verification code.');
}
if ($username === '' || $password === '') {
    login_error('Incorrect username or password.');
}

$stmt = $link->prepare(
    'SELECT u.user_id, u.username, u.firstname, u.lab, u.password, u.role,
            u.approved, u.is_verified, u.company_id, c.status AS company_status
     FROM users u
     LEFT JOIN companies c ON c.company_id = u.company_id
     WHERE u.username = ? LIMIT 1'
);
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    login_error('Incorrect username or password.');
}
if ($user['role'] !== 'reagently_admin' && (int) $user['is_verified'] !== 1) {
    login_error('Please verify your email before signing in.');
}
if ((int) $user['approved'] !== 1) {
    login_error('Your account is pending approval.');
}
if ($user['role'] === 'reagently_admin') {
    if ($user['company_id'] !== null) {
        login_error('Invalid platform administrator configuration.');
    }
} elseif (in_array($user['role'], ['admin', 'user'], true)) {
    if ($user['company_id'] === null || $user['company_status'] !== 'ACTIVE') {
        login_error('Your company is not active. Please contact the platform administrator.');
    }
} else {
    login_error('Invalid account role.');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['firstname'] = $user['firstname'];
$_SESSION['lab'] = $user['lab'];
$_SESSION['role'] = $user['role'];
$_SESSION['company_id'] = $user['company_id'] === null ? null : (int) $user['company_id'];

$link->close();
header('Location: ' . BASE_URL . ($user['role'] === 'reagently_admin' ? 'platform_admin.php' : 'index.php'));
exit;
