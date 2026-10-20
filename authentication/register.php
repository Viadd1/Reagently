<?php
/** Company-aware registration with the same markup and spacing as login.php. */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';

// Keep the CSRF token used by db_connections/insert_user.php.
if (empty($_SESSION['registration_csrf']) || !is_string($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}

// Regular accounts can only be registered within an ACTIVE company.
$companies = $link->query(
    "SELECT company_id, company_name FROM companies WHERE status = 'ACTIVE' ORDER BY company_name"
);
$hasActiveCompanies = $companies->num_rows > 0;

// Preserve the team's cookie-consent handling before any HTML output.
$show_cookie_banner = false;
$cookieConsentController = BASE_PATH . '/authentication/cookies_consent.php';
if (is_file($cookieConsentController)) {
    require $cookieConsentController;
}

$error = $_GET['error'] ?? '';
if (!is_string($error)) {
    $error = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Same responsive viewport, stylesheet, and typography as login.php. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Reagently - Register</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>

<?php include BASE_PATH . '/navbar.php'; ?>

<!-- Matches login.php: same page container and heading element. -->
<main class="page" style="margin-top: 20px;">

    <h2>Register to Reagently here!</h2>
    <h3>Create an account</h3>
    <p>Choose your company. After email verification, your company administrator must approve your account.</p>

    <?php if ($error !== ''): ?>
        <p role="alert" style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if (!$hasActiveCompanies): ?>
        <p role="status">No active companies are available yet. Please ask your Platform Admin to create and activate a company first.</p>
    <?php else: ?>

        <!-- Identical form-card, form-group, h3/label and line-break rhythm to login.php. -->
        <form
            class="form-card"
            action="<?= BASE_URL ?>db_connections/insert_user.php"
            method="POST"
        >
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['registration_csrf'], ENT_QUOTES, 'UTF-8') ?>">

            <!-- Company -->
            <div class="form-group">
                <h3><label for="reg-company">Company</label></h3>
                <select id="reg-company" name="company_id" required style="width: 286px; max-width: 100%;">
                    <option value="" selected disabled>Select your company</option>
                    <?php while ($company = $companies->fetch_assoc()): ?>
                        <option value="<?= (int)$company['company_id'] ?>">
                            <?= htmlspecialchars((string)$company['company_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <br>
            </div>
            <br>

            <!-- Username -->
            <div class="form-group">
                <h3><label for="reg-username">Username</label></h3>
                <input id="reg-username" type="text" name="username"
                       maxlength="100" pattern="[A-Za-z0-9]+" required autocomplete="username">
                <br>
            </div>
            <br>

            <!-- First name -->
            <div class="form-group">
                <h3><label for="reg-firstname">First name</label></h3>
                <input id="reg-firstname" type="text" name="firstname"
                       maxlength="100" autocomplete="given-name">
                <br>
            </div>
            <br>

            <!-- Last name -->
            <div class="form-group">
                <h3><label for="reg-lastname">Last name</label></h3>
                <input id="reg-lastname" type="text" name="lastname"
                       maxlength="100" autocomplete="family-name">
                <br>
            </div>
            <br>

            <!-- Lab -->
            <div class="form-group">
                <h3><label for="reg-lab">Lab / Group</label></h3>
                <input id="reg-lab" type="text" name="lab" maxlength="100">
                <br>
            </div>
            <br>

            <!-- Email -->
            <div class="form-group">
                <h3><label for="reg-email">Email</label></h3>
                <input id="reg-email" type="email" name="email"
                       maxlength="255" required autocomplete="email">
                <br>
            </div>
            <br>

            <!-- Password -->
            <div class="form-group">
                <h3><label for="reg-password">Password</label></h3>
                <input id="reg-password" type="password" name="password"
                       minlength="8" required autocomplete="new-password">
                <br>
            </div>
            <br>

            <!-- Confirm password -->
            <div class="form-group">
                <h3><label for="reg-confirm-password">Confirm password</label></h3>
                <input id="reg-confirm-password" type="password" name="confirm_password"
                       minlength="8" required autocomplete="new-password">
                <br>
            </div>

            <br><br>

            <!-- GDPR consent: its wording and required field are preserved. -->
            <div style="max-width: 650px; margin: 0 auto; text-align: left;">
                <label for="reg-gdpr" style="font-size: 18px; line-height: 1.6;">
                    <input id="reg-gdpr" name="gdpr_consent" type="checkbox"
                           value="1" required style="width: auto; height: auto;">
                    I consent to Reagently storing and processing my personal data in accordance with its
                    <a href="<?= BASE_URL ?>authentication/terms_of_service.php">Terms of Service</a>
                    and the applicable data protection requirements, including GDPR.
                </label>
            </div>

            <br><br>

            <!-- Same button markup as login.php. -->
            <input class="button" type="submit" value="Register">
        </form>

    <?php endif; ?>

    <p>Already have an account? <a href="<?= BASE_URL ?>authentication/login.php">Log in</a></p>
    <p><a href="<?= BASE_URL ?>authentication/terms_of_service.php">Terms of Service</a></p>

</main>

<!-- Cookie preferences are separate from mandatory registration consent. -->
<?php if ($show_cookie_banner): ?>
<section id="cookie-banner">
    <h2>Cookie preferences</h2>
    <p>Reagently uses essential cookies to keep the website working. You can accept or reject optional cookies.</p>
    <form method="POST" action="">
        <button type="submit" name="cookie_consent" value="accepted" id="accept-cookies">Accept cookies</button>
        <button type="submit" name="cookie_consent" value="rejected" id="reject-cookies">Reject optional cookies</button>
    </form>
</section>
<?php endif; ?>

</body>
</html>
<?php $link->close(); ?>
