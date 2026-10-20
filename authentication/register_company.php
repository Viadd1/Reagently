<?php
// Registration application for a company. A company admin must approve the user.
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';

$companies = $link->query("SELECT company_id, company_name FROM companies WHERE status = 'ACTIVE' ORDER BY company_name");
if (empty($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Company Registration</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <h2>Request an Account</h2>
    <p>Select your company. Your company's administrator must approve the account after email verification.</p>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form class="form-card" method="POST" action="<?= BASE_URL ?>db_connections/insert_user.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['registration_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group"><label>Company<br>
            <select name="company_id" required>
                <option value="">Select company</option>
                <?php while ($company = $companies->fetch_assoc()): ?>
                    <option value="<?= (int) $company['company_id'] ?>"><?= htmlspecialchars($company['company_name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endwhile; ?>
            </select>
        </label></div>
        <div class="form-group"><label>Username<br><input name="username" maxlength="100" required pattern="[A-Za-z0-9]+"></label></div>
        <div class="form-group"><label>First name<br><input name="firstname" maxlength="100"></label></div>
        <div class="form-group"><label>Last name<br><input name="lastname" maxlength="100"></label></div>
        <div class="form-group"><label>Lab<br><input name="lab" maxlength="100"></label></div>
        <div class="form-group"><label>Email<br><input name="email" type="email" maxlength="255" required></label></div>
        <div class="form-group"><label>Password<br><input name="password" type="password" minlength="8" required></label></div>
        <div class="form-group"><label>Confirm password<br><input name="confirm_password" type="password" minlength="8" required></label></div>
        <button class="button" type="submit">Request Account</button>
    </form>
</main>
</body>
</html>
<?php $link->close(); ?>
