<?php
/** Edit basic user profile. Role changes belong to the Platform Admin workflow. */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
require_once BASE_PATH . '/includes/user_management_csrf.php';
$companyId = tenant_require_user($link);
reagently_require_user_post_csrf();
$viewerId = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!is_int($targetId) || ($targetId !== $viewerId && !$isAdmin)) {
    http_response_code(403); exit('Cannot edit this account.');
}
$stmt = $link->prepare("SELECT user_id, username, firstname, lastname, lab, email, password, role
    FROM users WHERE user_id = ? AND company_id = ? AND role IN ('admin','user')");
$stmt->bind_param('ii', $targetId, $companyId); $stmt->execute();
$person=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$person) { http_response_code(404); exit('User not found in your company.'); }
$success = '';
if (array_key_exists('uname', $_POST)) {
    foreach (['uname','fname','lname','lab','email','password','confirm_password','current_password'] as $key) {
        if (isset($_POST[$key]) && !is_string($_POST[$key])) { http_response_code(400); exit('Invalid input.'); }
    }
    $username = trim($_POST['uname'] ?? '');
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $lab = trim($_POST['lab'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $current = $_POST['current_password'] ?? '';
    if (!preg_match('/^[A-Za-z0-9]{1,100}$/D', $username) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 ||
        strlen($fname)>100 || strlen($lname)>100 || strlen($lab)>100) {
        http_response_code(400); exit('Invalid profile fields.');
    }
    if ($password !== '') {
        if ($targetId !== $viewerId || strlen($password) < 8 || $password !== $confirm ||
            !password_verify($current, $person['password'])) {
            http_response_code(400); exit('Password change requires your current password and matching new passwords of at least eight characters.');
        }
    }
    try {
        $link->begin_transaction();
        $stmt = $link->prepare('UPDATE users SET username=?, firstname=?, lastname=?, lab=?, email=? WHERE user_id=? AND company_id=?');
        $stmt->bind_param('sssssii', $username,$fname,$lname,$lab,$email,$targetId,$companyId);
        $stmt->execute(); $stmt->close();
        if ($password !== '') {
            $hash=password_hash($password, PASSWORD_DEFAULT);
            $stmt=$link->prepare('UPDATE users SET password=? WHERE user_id=? AND company_id=?');
            $stmt->bind_param('sii',$hash,$targetId,$companyId);
            $stmt->execute(); $stmt->close();
        }
        $link->commit();
        if ($targetId === $viewerId) {
            $_SESSION['username'] = $username;
            $_SESSION['firstname'] = $fname;
            $_SESSION['lab'] = $lab;
        }
        $success = 'User information updated successfully.';
    } catch (mysqli_sql_exception $e) {
        $link->rollback();
        $success = (int)$e->getCode() === 1062 ? 'Username or email already exists.' : 'Unable to update user.';
    }
    $stmt=$link->prepare("SELECT user_id, username, firstname, lastname, lab, email, password, role
        FROM users WHERE user_id=? AND company_id=? AND role IN ('admin','user')");
    $stmt->bind_param('ii',$targetId,$companyId);$stmt->execute();
    $person=$stmt->get_result()->fetch_assoc();$stmt->close();
}
$csrf=reagently_user_csrf_token();
$h=static fn($v): string=>htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reagently - Edit User</title><link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head>
<body><?php include BASE_PATH . '/navbar.php'; ?><main class="page">
<h2>Edit User</h2>
<?php if ($success !== ''): ?><p><?= $h($success) ?></p><?php endif; ?>
<p>Company administrators may edit profiles within their company. Role changes must be managed by the platform administrator.</p>
<form method="POST" action="<?= BASE_URL ?>db_connections/edit_user.php">
<input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
<input type="hidden" name="user_id" value="<?= (int)$targetId ?>">
<label>Username <input type="text" name="uname" maxlength="100" required value="<?= $h($person['username']) ?>"></label><br><br>
<label>First name <input type="text" name="fname" maxlength="100" value="<?= $h($person['firstname']) ?>"></label><br><br>
<label>Last name <input type="text" name="lname" maxlength="100" value="<?= $h($person['lastname']) ?>"></label><br><br>
<label>Lab <input type="text" name="lab" maxlength="100" value="<?= $h($person['lab']) ?>"></label><br><br>
<label>Email <input type="email" name="email" maxlength="255" required value="<?= $h($person['email']) ?>"></label><br><br>
<?php if ($targetId === $viewerId): ?>
<label>Current password (required to change password) <input type="password" name="current_password" autocomplete="current-password"></label><br><br>
<label>New password <input type="password" name="password" autocomplete="new-password"></label><br><br>
<label>Confirm new password <input type="password" name="confirm_password" autocomplete="new-password"></label><br><br>
<?php endif; ?>
<button class="button" type="submit">Save changes</button>
</form><br>
<?php if (($person['role'] ?? '') === 'user'): ?>
<form method="POST" action="<?= BASE_URL ?>db_connections/delete_user.php"
 onsubmit="return confirm('Delete this account? You must first complete or cancel ACTIVE reservations. Older reservations and inventory history will be assigned to this company's null user; the original account will be anonymised. This cannot be undone.');">
<input type="hidden" name="user_id" value="<?= (int)$targetId ?>">
<input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
<button class="button" type="submit">Delete account</button>
</form>
<?php endif; ?>
<a class="button" href="<?= BASE_URL ?>user.php">Back to User</a>
</main></body></html>
<?php $link->close(); ?>
