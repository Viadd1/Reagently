<?php
/*
Notice box for administrators: shows how many reagent lots are already
expired or expire within the next month (and are still in stock).
Include it on any page after init.php has started the session.
It prints nothing for non-admins, or when no lot is expiring.
*/

if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {

    // Use the page's existing database connection if there is one
    if (!isset($link)) {
        include BASE_PATH . "/db.php";
    }

    require_once BASE_PATH . '/includes/tenant_auth.php';
    $companyId = tenant_require_admin($link);
    $expiryStmt = $link->prepare(
        "SELECT
            COALESCE(SUM(l.expiry_date < CURDATE()), 0)  AS expired,
            COALESCE(SUM(l.expiry_date >= CURDATE()), 0) AS expiring
         FROM reagent_lots l
         JOIN reagents r ON r.reagent_id = l.reagent_id
         JOIN locations loc ON loc.location_id = l.location_id
         WHERE r.company_id = ? AND loc.company_id = ? AND r.is_deleted = 0
           AND l.current_quantity > 0
           AND l.expiry_date IS NOT NULL
           AND l.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)"
    );
    $expiryStmt->bind_param('ii', $companyId, $companyId);
    $expiryStmt->execute();
    $expiry_counts = $expiryStmt->get_result()->fetch_assoc();
    $expiryStmt->close();
    $expiry_expired  = (int)$expiry_counts['expired'];
    $expiry_expiring = (int)$expiry_counts['expiring'];

    if ($expiry_expired + $expiry_expiring > 0) {

        $expiry_parts = [];
        if ($expiry_expired > 0) {
            $expiry_parts[] = $expiry_expired . " lot(s) already expired";
        }
        if ($expiry_expiring > 0) {
            $expiry_parts[] = $expiry_expiring . " lot(s) expiring within 1 month";
        }
?>
<div style="background:#fff3cd; border:1px solid #e0a800; border-radius:8px;
            padding:15px 20px; margin:20px auto; max-width:700px; text-align:center;">
    <strong>Expiry warning:</strong>
    <?= htmlspecialchars(implode(" and ", $expiry_parts)) ?>.
    <br><br>
    <a class="button" href="/admin/expiring_reagents.php">View expiring reagents</a>
</div>
<?php
    }
}
