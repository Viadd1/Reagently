<?php
/**
 * Record the final actual usage for exactly one ACTIVE reservation.
 * Concurrency: lock reagent_lots FIRST, then reservation; use one transaction.
 * Physical stock decreases by actual usage, not by reservation quantity.
 * Remaining reserved amount becomes available when reservation becomes COMPLETED.
 */
require_once __DIR__ . '/../init.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
$expectedCsrf = (string) ($_SESSION['csrf_token'] ?? '');
$submittedCsrf = (string) ($_POST['csrf_token'] ?? '');
if ($expectedCsrf === '' || !hash_equals($expectedCsrf, $submittedCsrf)) {
    http_response_code(403);
    exit('Invalid request token. Reload Record Usage and retry.');
}

$userId = (int) $_SESSION['user_id'];
$reservationId = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
$usedRaw = trim((string) ($_POST['quantity_used'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));
if (!$reservationId || $reservationId <= 0 ||
    !preg_match('/^\d+(?:\.\d{1,2})?$/D', $usedRaw) ||
    (float) $usedRaw > 99999999.99 || strlen($note) > 2000) {
    http_response_code(400);
    exit('Invalid reservation, usage quantity, or note. Quantity must be nonnegative with up to two decimals.');
}
$usedCents = (int) round((float) $usedRaw * 100);
require BASE_PATH . '/db.php';

// Company Data Isolation
require_once BASE_PATH . '/includes/tenant_auth.php';
// Get current user's company ID
$companyId = tenant_require_user($link);

// This lookup happens OUTSIDE the transaction to avoid creating a stale InnoDB
// snapshot before the lot lock. Ownership and status are rechecked under lock.
$stmt = $link->prepare(
    'SELECT res.lot_id FROM reservations res
     JOIN projects p ON res.project_id = p.project_id
     WHERE res.reservation_id = ? AND res.user_id = ? AND p.company_id = ?');


$stmt->bind_param('iii', $reservationId, $userId, $companyId);
$stmt->execute();
$lookup = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$lookup) {
    http_response_code(404);
    exit('Reservation not found.');
}
$lotId = (int) $lookup['lot_id'];
$link->begin_transaction();
try {
    // All writers touching the same lot MUST take this lock before checking
    // ACTIVE reservations. It serializes Usage, Cancel, Reserve, Adjust.
    $stmt = $link->prepare(
        'SELECT l.current_quantity FROM reagent_lots l
         JOIN reagents r ON l.reagent_id = r.reagent_id
         JOIN locations loc ON l.location_id = loc.location_id
         WHERE l.lot_id = ? AND r.company_id = ? AND loc.company_id = ? AND r.is_deleted = 0
         FOR UPDATE');

    $stmt->bind_param('iii', $lotId, $companyId, $companyId);

    $stmt->execute();
    $lot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lot) {
        throw new DomainException('The reagent lot no longer exists.');
    }

    // Lock reservation only after the lot, keeping a consistent lock order.
    $stmt = $link->prepare(
        'SELECT res.quantity_reserved, res.status FROM reservations res
         JOIN projects p ON res.project_id = p.project_id
         WHERE res.reservation_id = ? AND res.user_id = ? AND res.lot_id = ? AND p.company_id = ?
         FOR UPDATE'
    );
    $stmt->bind_param('iiii', $reservationId, $userId, $lotId, $companyId);
    $stmt->execute();
    $reservation = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$reservation || $reservation['status'] !== 'ACTIVE') {
        throw new DomainException('Usage was already recorded, the reservation was cancelled, or it is not yours.');
    }

    $reservedCents = (int) round((float) $reservation['quantity_reserved'] * 100);
    $physicalCents = (int) round((float) $lot['current_quantity'] * 100);
    if ($usedCents > $reservedCents) {
        throw new DomainException('Actual usage cannot exceed the reserved quantity.');
    }

    $stmt = $link->prepare(
        "SELECT COALESCE(SUM(quantity_reserved), 0) AS active_reserved
         FROM reservations WHERE lot_id = ? AND status = 'ACTIVE'"
    );
    $stmt->bind_param('i', $lotId);
    $stmt->execute();
    $totals = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $activeCents = (int) round((float) $totals['active_reserved'] * 100);
    if ($physicalCents < $activeCents) {
        throw new RuntimeException('Inventory inconsistency: reservations exceed physical stock.');
    }

    $stmt = $link->prepare(
        "UPDATE reservations SET status = 'COMPLETED'
         WHERE reservation_id = ? AND user_id = ? AND lot_id = ? AND status = 'ACTIVE'"
    );
    $stmt->bind_param('iii', $reservationId, $userId, $lotId);
    $stmt->execute();
    $completed = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$completed) {
        throw new DomainException('This reservation has already been processed.');
    }

    $usedAmount = number_format($usedCents / 100, 2, '.', '');
    if ($usedCents > 0) {
        $stmt = $link->prepare(
            'UPDATE reagent_lots SET current_quantity = current_quantity - ?
             WHERE lot_id = ? AND current_quantity >= ?'
        );
        $stmt->bind_param('did', $usedAmount, $lotId, $usedAmount);
        $stmt->execute();
        $updated = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$updated) {
            throw new RuntimeException('Physical quantity update failed.');
        }
    }

    // quantity_change always means PHYSICAL delta (RESERVE/CANCEL log 0).
    $negativeUsage = number_format(-$usedCents / 100, 2, '.', '');
    $unused = number_format(($reservedCents - $usedCents) / 100, 2, '.', '');
    $details = "Used {$usedAmount}; released {$unused} from ACTIVE reservation.";
    if ($note !== '') {
        $details .= " Note: {$note}";
    }
    $stmt = $link->prepare(
        "INSERT INTO inventory_transactions
         (lot_id, user_id, reservation_id, transaction_type, quantity_change, note)
         VALUES (?, ?, ?, 'USAGE', ?, ?)"
    );
    $stmt->bind_param('iiids', $lotId, $userId, $reservationId, $negativeUsage, $details);
    $stmt->execute();
    $stmt->close();
    $link->commit();
} catch (Throwable $e) {
    $link->rollback();
    if ($e instanceof DomainException) {
        http_response_code(409);
        exit(htmlspecialchars($e->getMessage(), ENT_QUOTES));
    }
    error_log('Reagently usage transaction failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to record usage. No database changes were saved.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reagently - Usage Recorded</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
  <h2>Usage Recorded</h2>
  <p>Actual usage: <?= htmlspecialchars($usedAmount, ENT_QUOTES) ?></p>
  <p>Unused reservation released: <?= htmlspecialchars($unused, ENT_QUOTES) ?></p>
  <p>The reservation is now COMPLETED. Physical stock has been reduced by the amount actually used.</p>
  <a class="button" href="<?= BASE_URL ?>user/reservations.php">Back to Reservations</a>
</main>
</body>
</html>
