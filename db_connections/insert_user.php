<?php
// Public company account request. No automatic admin role, no automatic approval.
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required.');
}

function registration_fail(string $message): never
{
    header('Location: ' . BASE_URL . 'authentication/register_company.php?error=' . rawurlencode($message));
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!is_string($csrf) || !isset($_SESSION['registration_csrf']) ||
    !is_string($_SESSION['registration_csrf']) ||
    !hash_equals($_SESSION['registration_csrf'], $csrf)) {
    http_response_code(403);
    exit('Invalid registration token. Open the registration page again.');
}

$companyId = filter_var($_POST['company_id'] ?? null, FILTER_VALIDATE_INT);

// Reject non-text input rather than casting attacker-supplied arrays to strings.
foreach (['username', 'firstname', 'lastname', 'lab', 'email', 'password', 'confirm_password'] as $field) {
    if (isset($_POST[$field]) && !is_string($_POST[$field])) {
        registration_fail('Invalid form data.');
    }
}

$username = trim($_POST['username'] ?? '');
$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$lab = trim($_POST['lab'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$companyId || $companyId < 1) {
    registration_fail('Please select a company.');
}
if (!preg_match('/^[A-Za-z0-9]{1,100}$/D', $username) || strtolower($username) === 'null') {
    registration_fail('Username must contain only letters and numbers.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    registration_fail('Invalid email address.');
}
if (strlen($password) < 8) {
    registration_fail('Password must be at least 8 characters long.');
}

if ($password !== $confirm) {
    registration_fail('Passwords do not match.');
}

foreach ([$firstname, $lastname, $lab] as $field) {
    if ((function_exists('mb_strlen') ? mb_strlen($field, 'UTF-8') : strlen($field)) > 100) {
        registration_fail('One of the names or lab fields is too long.');
    }
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));

try {
    $link->begin_transaction();

    // Only users of an active company can register.
    $stmt = $link->prepare(
        "SELECT company_id FROM companies WHERE company_id = ? AND status = 'ACTIVE' FOR UPDATE"
    );
    $stmt->bind_param('i', $companyId);
    $stmt->execute();
    $companyExists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$companyExists) {
        $link->rollback();
        registration_fail('Company is not accepting registrations.');
    }

    // All new registrations remain ordinary users, pending email verification and approval.
    $stmt = $link->prepare(
        'INSERT INTO users (username, firstname, lastname, lab, email, password, role,
                            approved, is_verified, company_id, verification_token, token_expires_at)
         VALUES (?, ?, ?, ?, ?, ?, \'user\', 0, 0, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
    );
    $stmt->bind_param(
        'ssssssis',
        $username,
        $firstname,
        $lastname,
        $lab,
        $email,
        $passwordHash,
        $companyId,
        $token
    );
    $stmt->execute();
    $stmt->close();
    $link->commit();
} catch (mysqli_sql_exception $e) {
    $link->rollback();
    error_log('Registration failed: ' . $e->getMessage());
    registration_fail((int) $e->getCode() === 1062 ? 'Username or email already registered.' : 'Database error.');
}

unset($_SESSION['registration_csrf']);

// SMTP is optional in local testing. Never claim a verification email was sent unless it was.
$emailSent = false;
if (is_file(BASE_PATH . '/vendor/autoload.php') && is_file(BASE_PATH . '/.env')) {
    try {
        require_once BASE_PATH . '/send_email.php';
        $base = getenv('REAGENTLY_APP_URL') ?: '';
        if ($base === '') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
            if (preg_match('/^(localhost|127\.0\.0\.1)(:[0-9]{1,5})?$/D', $host)) {
                $base = 'http://' . $host;
            }
        }
        if ($base !== '') {
            $url = rtrim($base, '/') . BASE_URL . 'authentication/verify_email.php?token=' . rawurlencode($token);
            $safeUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $body = '<p>Hi ' . $safeUser . ',</p><p>Please verify your email within 24 hours:</p>' .
                    '<p><a href="' . $safeUrl . '">Verify Email</a></p>';
            $emailSent = \Email\sendEmail($email, $username, 'Verify your Reagently email', $body);
        }
    } catch (Throwable $e) {
        error_log('Registration verification email failed: ' . $e->getMessage());
    }
}
$link->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Account request</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL . 'style.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <h2>Account request saved</h2>
    <?php if ($emailSent): ?>
        <p>Check your email for a verification link. After verification, your company administrator must approve the account.</p>
    <?php else: ?>
        <p>Your account request was saved, but the verification email was not sent. Ask the TEST administrator to verify this account in the TEST database. Company approval is still required.</p>
    <?php endif; ?>
    <a class="button" href="<?= htmlspecialchars(BASE_URL . 'authentication/login.php', ENT_QUOTES, 'UTF-8') ?>">Go to Login</a>
</main>
</body>
</html>
