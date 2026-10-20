<?php
/** Reagently home: company-scoped announcements and expiry warnings, plus cookie consent. */
require_once __DIR__ . '/init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

$isCompanyMember = false;
$announcements = null;
$expiryCounts = null;
$show_cookie_banner = false;

if (isset($_SESSION['user_id'])) {
    // Confirm the actual role in the database before granting platform-admin access.
    $currentUserId = (int) $_SESSION['user_id'];
    $roleCheck = $link->prepare('SELECT role FROM users WHERE user_id = ? LIMIT 1');
    $roleCheck->bind_param('i', $currentUserId);
    $roleCheck->execute();
    $roleRow = $roleCheck->get_result()->fetch_assoc();
    $roleCheck->close();

    if ($roleRow && $roleRow['role'] === 'reagently_admin') {
        // Platform admins may see the home page but never company-specific data.
        $_SESSION['role'] = 'reagently_admin';
    } else {
        // Reject unverified/unapproved users and users in inactive companies.
        $companyId = tenant_require_user($link);
        $isCompanyMember = true;

        $stmt = $link->prepare(
            'SELECT title, message, author_name, created_at
             FROM announcements
             WHERE company_id = ?
             ORDER BY created_at DESC
             LIMIT 10'
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $announcements = $stmt->get_result();
        $stmt->close();

        if (($_SESSION['role'] ?? '') === 'admin') {
            // Expiry counts must be limited to the administrator's company.
            $exp = $link->prepare(
                'SELECT
                     COALESCE(SUM(CASE WHEN l.expiry_date < CURDATE() THEN 1 ELSE 0 END), 0) AS expired,
                     COALESCE(SUM(CASE WHEN l.expiry_date >= CURDATE() THEN 1 ELSE 0 END), 0) AS expiring
                 FROM reagent_lots l
                 JOIN reagents r ON r.reagent_id = l.reagent_id
                 JOIN locations loc ON loc.location_id = l.location_id
                 WHERE r.company_id = ? AND loc.company_id = ?
                   AND r.is_deleted = 0 AND l.current_quantity > 0
                   AND l.expiry_date IS NOT NULL
                   AND l.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)'
            );
            $exp->bind_param('ii', $companyId, $companyId);
            $exp->execute();
            $expiryCounts = $exp->get_result()->fetch_assoc();
            $exp->close();
        }
    }
}

// Retain main's cookie-consent workflow; the included file defines
// $show_cookie_banner and may process a submitted consent preference.
// The variable is initialized above to avoid an undefined-variable warning.
include BASE_PATH . '/authentication/cookies_consent.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Home</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL . 'style.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>

<section class="welcome">
    <?php if (isset($_SESSION['user_id'])): ?>
        <h2>Welcome <?= htmlspecialchars((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?>!</h2>
    <?php else: ?>
        <h2>Welcome to Reagently!</h2>
        <br><br>
        <h3>Please log in to access the Reagently web.</h3>
    <?php endif; ?>

    <br><br>
    <p>
        Reagently is a shared laboratory reagent management system for tracking
        reagent availability, storage locations, reservations and usage.
    </p>
</section>

<?php if ($expiryCounts && ((int) $expiryCounts['expired'] + (int) $expiryCounts['expiring'] > 0)): ?>
    <div class="expiry-notice" style="padding:15px 20px; margin:20px auto; max-width:700px; text-align:center; border:1px solid #ccc; border-radius:8px;">
        <strong>Expiry warning (your company):</strong>
        <?= (int) $expiryCounts['expired'] ?> lot(s) already expired;
        <?= (int) $expiryCounts['expiring'] ?> lot(s) expiring within one month.
    </div>
<?php endif; ?>

<?php if ($isCompanyMember): ?>
    <section class="news">
        <h2>
            Recent Updates from your lab<?= isset($_SESSION['lab']) ? ': ' . htmlspecialchars((string) $_SESSION['lab'], ENT_QUOTES, 'UTF-8') : ':' ?>
        </h2>
        <div class="news-list">
            <?php if ($announcements && $announcements->num_rows > 0): ?>
                <?php while ($a = $announcements->fetch_assoc()): ?>
                    <article class="news-item">
                        <h3><?= htmlspecialchars((string) $a['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="news-date">
                            <?= htmlspecialchars(date('j F Y', strtotime($a['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                            &middot; by <?= htmlspecialchars((string) $a['author_name'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p><?= nl2br(htmlspecialchars((string) $a['message'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No announcements yet.</p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($show_cookie_banner): ?>
    <section id="cookie-banner">
        <h2>Cookie preferences</h2>
        <p>
            Reagently uses essential cookies to keep the website working.
            You can accept or reject optional cookies.
        </p>
        <form method="post">
            <button type="submit" name="cookie_consent" value="accepted" id="accept-cookies">
                Accept cookies
            </button>
            <button type="submit" name="cookie_consent" value="rejected" id="reject-cookies">
                Reject optional cookies
            </button>
        </form>
    </section>
<?php endif; ?>
</body>
</html>
<?php $link->close(); ?>
