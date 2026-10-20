<?php
/**
 * Cancels an ACTIVE reservation with a POST + CSRF token.
 * Serializes with other lot changes by locking reagent_lots first.
 */
require_once __DIR__ . '/../init.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed. Please cancel from the reservations page.');
}
$csrfSent = (string) ($_POST['csrf_token'] ?? '');
$csrfExpected = (string) ($_SESSION['csrf_token'] ?? '');
if ($csrfExpected === '' || !hash_equals($csrfExpected, $csrfSent)) {
    http_response_code(403);
    exit('Invalid or expired request token. Reload your reservations page.');
}
$userId = (int) $_SESSION['user_id'];
$reservationId = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
if (!$reservationId || $reservationId <= 0) {
    http_response_code(400);
    exit('Invalid reservation ID.');
}
require BASE_PATH . '/db.php';

// Company Data Isolation
require_once BASE_PATH . '/includes/tenant_auth.php';

// Get current user's company ID
$companyId = tenant_require_user($link);

// Read the lot ID before beginning the transaction. This non-locking read
// cannot create a stale transaction snapshot; update below revalidates status.
$stmt = $link->prepare(
    'SELECT res.lot_id, res.quantity_reserved FROM reservations res
    JOIN projects p ON res.project_id = p.project_id
    WHERE res.reservation_id = ? AND res.user_id = ? AND p.company_id = ?'
);
$stmt->bind_param('iii', $reservationId, $userId, $companyId);

$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cancelled = false;
if ($reservation) {
    $lotId = (int) $reservation['lot_id'];
    $link->begin_transaction();
    try {
        // Same lock order as reservation confirmation / quantity adjustment.
        $stmt = $link->prepare('SELECT  l.lot_id FROM reagent_lots l
        JOIN reagents r ON l.reagent_id = r.reagent_id
        JOIN locations loc ON l.location_id = loc.location_id
        WHERE l.lot_id = ? AND r.company_id = ? AND loc.company_id = ? AND r.is_deleted = 0
        FOR UPDATE');
        $stmt->bind_param('iii', $lotId, $companyId, $companyId);

        $stmt->execute();
        $exists = $stmt->get_result()->num_rows === 1;
        $stmt->close();
        if (!$exists) {
            throw new RuntimeException('Reservation lot does not exist.');
        }

        $stmt = $link->prepare(
            "UPDATE reservations res
            JOIN projects p ON res.project_id = p.project_id
            SET status = 'CANCELLED'
             WHERE res.reservation_id = ? AND res.user_id = ? AND res.lot_id = ? AND res.status = 'ACTIVE' AND p.company_id = ?");
        $stmt->bind_param('iiii', $reservationId, $userId, $lotId, $companyId);
        $stmt->execute();
        $cancelled = $stmt->affected_rows === 1;
        $stmt->close();

        if ($cancelled) {
            // Release of reservation changes available, not physical quantity.
            $note = 'Cancelled reservation of ' . $reservation['quantity_reserved'] . '; physical stock unchanged.';
            $zeroPhysicalChange = '0.00';
            $stmt = $link->prepare(
                "INSERT INTO inventory_transactions
                 (lot_id, user_id, reservation_id, transaction_type, quantity_change, note)
                 VALUES (?, ?, ?, 'CANCEL', ?, ?)"
            );
            $stmt->bind_param('iiids', $lotId, $userId, $reservationId, $zeroPhysicalChange, $note);
            $stmt->execute();
            $stmt->close();
            $link->commit();
        } else {
            $link->rollback();
        }
    } catch (Throwable $e) {
        $link->rollback();
        error_log('Reagently cancellation failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Unable to cancel reservation. No changes were saved.');
    }
}
// Keep the DB connection available for navbar.php if it needs it.
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reagently - Cancel Reservation</title>
<link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>style.css"></head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <h2>Cancel Reservation</h2>
    <p><?= $cancelled ? 'Reservation cancelled successfully.' : 'The reservation does not exist, is not yours, or is no longer ACTIVE.' ?></p>
    <a class="button" href="<?= BASE_URL ?>user/reservations.php">Back to reservations</a>
</main>
</body></html>
