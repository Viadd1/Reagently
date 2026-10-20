<?php
/** Phase 3A: company-scoped home announcements and expiry counters. */
require_once __DIR__ . '/init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

$isCompanyMember = false;
$announcements = null;
$expiryCounts = null;

if (isset($_SESSION['user_id'])) {
    // Platform admins may view home, but must never receive a company's data.
    $currentUserId = (int) $_SESSION['user_id'];
    $roleCheck = $link->prepare('SELECT role FROM users WHERE user_id = ? LIMIT 1');
    $roleCheck->bind_param('i', $currentUserId);
    $roleCheck->execute();
    $roleRow = $roleCheck->get_result()->fetch_assoc();
    $roleCheck->close();

    if ($roleRow && $roleRow['role'] === 'reagently_admin') {
        $_SESSION['role'] = 'reagently_admin';
    } else {
        // Reject unverified, unapproved, suspended and orphaned accounts.
        $companyId = tenant_require_user($link);
        $isCompanyMember = true;
        $stmt = $link->prepare(
            'SELECT title, message, author_name, created_at
             FROM announcements
             WHERE company_id = ?
             ORDER BY created_at DESC LIMIT 10'
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $announcements = $stmt->get_result();
        $stmt->close();

        if (($_SESSION['role'] ?? '') === 'admin') {
            // Safe replacement for unscoped admin/expiry_notice.php on home.
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
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Home</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >
</head>
<body>

<?php
// Main header and navigation bar
include "navbar.php";
?>

<!-- Welcome section -->
<section class="welcome">

    <?php

    // Personalized welcome message for logged-in users
    if (isset($_SESSION['user_id'])) {

        echo
        "<h2>
            Welcome " .
            htmlspecialchars(
                ($_SESSION["firstname"] ?? $_SESSION["username"] ?? "User")
            ) .
            "!
        </h2>";

    } else {
        echo
        "<h2>
            Welcome to Reagently!
        </h2>
        <br>
        <br>

        <h3>
        Please log in to access the Reagently web.
        </h3>";
    }

    ?>

    <br>
    <br>

    <p>

        Reagently is a shared laboratory reagent
        management system for tracking reagent
        availability, storage locations,
        reservations and usage.

    </p>

</section>

<?php if ($expiryCounts && ((int)$expiryCounts['expired'] + (int)$expiryCounts['expiring'] > 0)): ?>
    <div class="expiry-notice" style="padding:15px 20px; margin:20px auto; max-width:700px; text-align:center; border:1px solid #ccc; border-radius:8px;">
        <strong>Expiry warning (your company):</strong>
        <?= (int)$expiryCounts['expired'] ?> lot(s) already expired;
        <?= (int)$expiryCounts['expiring'] ?> lot(s) expiring within one month.
        <!-- Phase 3: no link to unscoped expiring_reagents.php until that endpoint is fixed. -->
    </div>
<?php endif; ?>

<!-- Department news -->
<?php
if ($isCompanyMember) {
?>

<section class="news">
<?php

    // Personalized department in news section for logged-in users
    if (isset($_SESSION['lab'])) {

        echo
        "<h2>
            Recent Updates from your lab: " .
            htmlspecialchars(
                $_SESSION['lab']
            ) .
            "
        </h2>";

    } else {

        echo
        "<h2>
            Recent Updates from your lab:
        </h2>";

    }

    ?>

    <div class="news-list">

        <?php if ($announcements->num_rows > 0): ?>

            <?php while ($a = $announcements->fetch_assoc()): ?>

                <article class="news-item">

                    <h3><?= htmlspecialchars($a['title']) ?></h3>

                    <p class="news-date">
                        <?= date('j F Y', strtotime($a['created_at'])) ?>
                        &middot; by <?= htmlspecialchars($a['author_name']) ?>
                    </p>

                    <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No announcements yet.</p>

        <?php endif; ?>

    </div>

</section>

<?php }
?>

</body>
</html>

<?php $link->close(); ?>