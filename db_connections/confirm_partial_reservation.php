<?php
/**
 * Atomic reservation / partial-order confirmation.
 * ALL reservation writers must lock reagent_lots first, then inspect ACTIVE reservations.
 * Database schema: available = current_quantity - sum(ACTIVE quantity_reserved).
 */
require_once __DIR__ . '/../init.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}
$userId = (int) $_SESSION['user_id'];
$projectId = filter_var($_POST['project_id'] ?? null, FILTER_VALIDATE_INT);
$lotId = filter_var($_POST['lot_id'] ?? null, FILTER_VALIDATE_INT);
$requestedRaw = trim((string) ($_POST['quantity_reserved'] ?? ''));
$neededDate = (string) ($_POST['needed_date'] ?? '');
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $neededDate);
if (!$projectId || $projectId <= 0 || !$lotId || $lotId <= 0 ||
    !preg_match('/^\d+(?:\.\d{1,2})?$/D', $requestedRaw) ||
    (float) $requestedRaw <= 0 || (float) $requestedRaw > 99999999.99 ||
    !$date || $date->format('Y-m-d') !== $neededDate) {
    http_response_code(400);
    exit('Invalid reservation details.');
}
$requestedCents = (int) round(((float) $requestedRaw) * 100);
// Confirm only once, and only for the exact details shown in the preview.
$decision = (string) ($_POST['decision'] ?? '');
$token = (string) ($_POST['confirmation_token'] ?? '');
$expectedDigest = (string) ($_SESSION['reservation_confirmations'][$token] ?? '');
$actualDigest = hash('sha256', implode('|', [$projectId, $lotId, $requestedCents, $neededDate]));
if (!in_array($decision, ['confirm', 'cancel'], true) ||
    $token === '' || $expectedDigest === '' || !hash_equals($expectedDigest, $actualDigest)) {
    http_response_code(409);
    exit('This confirmation was already processed or has expired. Start a new reservation.');
}
// PHP session requests for the same user are serialized in the default file session handler.
// Consuming this token prevents repeat POST from this session.
unset($_SESSION['reservation_confirmations'][$token]);
if ($decision === 'cancel') {
    header('Location: ' . BASE_URL . 'user/make_reservation.php');
    exit;
}


require BASE_PATH . '/db.php';
// Company Data Isolation
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_user($link);

// Start transaction
$link->begin_transaction();
try {
    // FIRST database read after BEGIN must lock the lot.
    // In InnoDB REPEATABLE READ a preceding ordinary SELECT (such as membership)
    // could create an old consistent-read snapshot while another user holds this lot lock.
    $stmt = $link->prepare(
        'SELECT l.current_quantity, l.reagent_id, r.name AS reagent_name
         FROM reagent_lots l
         JOIN reagents r ON r.reagent_id = l.reagent_id
         JOIN locations loc ON loc.location_id = l.location_id
         WHERE l.lot_id = ? AND r.company_id = ? AND loc.company_id = ? AND r.is_deleted = 0
         FOR UPDATE'
    );
    $stmt->bind_param('iii', $lotId, $companyId, $companyId);
    $stmt->execute();
    $lot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lot) {
        throw new DomainException('Selected reagent lot does not exist.');
    }

    // Membership check AFTER taking the lot lock ensures the subsequent normal
    // SUM uses a fresh read-view. Never rely on the earlier preview.
    $stmt = $link->prepare(
        'SELECT 1 FROM project_members pm
         JOIN projects p ON pm.project_id = p.project_id
         WHERE pm.project_id = ? AND pm.user_id = ? AND p.company_id = ?');
    $stmt->bind_param('iii', $projectId, $userId, $companyId);
    $stmt->execute();
    $isMember = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    if (!$isMember) {
        throw new DomainException('You are not a member of this project.');
    }

    $stmt = $link->prepare(
        "SELECT COALESCE(SUM(quantity_reserved), 0) AS reserved
         FROM reservations WHERE lot_id = ? AND status = 'ACTIVE'"
    );
    $stmt->bind_param('i', $lotId);
    $stmt->execute();
    $reserved = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // The MySQL columns are DECIMAL(10,2); cents avoid PHP float subtraction errors.
    $physicalCents = (int) round(((float) $lot['current_quantity']) * 100);
    $reservedCents = (int) round(((float) $reserved['reserved']) * 100);
    if ($reservedCents > $physicalCents) {
        throw new RuntimeException('Inventory inconsistency: active reservations exceed physical stock.');
    }
    $reserveCents = min($requestedCents, $physicalCents - $reservedCents);
    $orderCents = $requestedCents - $reserveCents;
    $reservationQty = number_format($reserveCents / 100, 2, '.', '');
    $orderQty = number_format($orderCents / 100, 2, '.', '');

    if ($reserveCents > 0) {
        $stmt = $link->prepare(
            'INSERT INTO reservations
             (user_id, project_id, lot_id, quantity_reserved, needed_date)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iiids', $userId, $projectId, $lotId, $reservationQty, $neededDate);
        $stmt->execute();
        $reservationId = $link->insert_id;
        $stmt->close();

        // RESERVE affects availability, NOT the physical current_quantity.
        $note = "Reserved {$reservationQty}; physical stock unchanged.";
        $zeroPhysicalChange = '0.00';
        $stmt = $link->prepare(
            "INSERT INTO inventory_transactions
             (lot_id, user_id, reservation_id, transaction_type, quantity_change, note)
             VALUES (?, ?, ?, 'RESERVE', ?, ?)"
        );
        $stmt->bind_param('iiids', $lotId, $userId, $reservationId, $zeroPhysicalChange, $note);
        $stmt->execute();
        $stmt->close();
    }

    if ($orderCents > 0) {
        $reagentId = (int) $lot['reagent_id'];
        $reagentName = (string) $lot['reagent_name'];
        $requestText = "Please order {$orderQty} of {$reagentName}. Needed by {$neededDate}.";
        $stmt = $link->prepare(
            'INSERT INTO order_requests (user_id, reagent_id, reagent_name, request_text)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iiss', $userId, $reagentId, $reagentName, $requestText);
        $stmt->execute();
        $stmt->close();
    }

    $link->commit();
} catch (Throwable $e) {
    $link->rollback();
    $link->close();
    if ($e instanceof DomainException) {
        http_response_code(400);
        exit(htmlspecialchars($e->getMessage(), ENT_QUOTES));
    }
    error_log('Reagently reservation transaction failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not complete your reservation. No changes were saved. Please retry.');
}
// Keep the DB connection available for navbar.php if it needs it.
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Reservation Result</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <?php if ($reserveCents === 0 && $orderCents > 0): ?>
        <h2>Out of Stock - Order Request Submitted</h2>
        <p><strong>No reagent was reserved.</strong> The available stock was checked again when you confirmed.</p>
        <p>An order request for <?= htmlspecialchars($orderQty, ENT_QUOTES) ?> was submitted to the administrator for review.</p>
        <p>This does not mean that the supplier order has been placed yet.</p>
    <?php elseif ($reserveCents > 0 && $orderCents > 0): ?>
        <h2>Partial Reservation Confirmed</h2>
        <p>Part of your requested quantity was reserved successfully.</p>
        <p>The remaining <?= htmlspecialchars($orderQty, ENT_QUOTES) ?> was submitted as an order request for administrator review.</p>
    <?php elseif ($reserveCents > 0): ?>
        <h2>Reservation Confirmed</h2>
        <p>Your reagent reservation was created successfully.</p>
    <?php else: ?>
        <h2>No Changes Made</h2>
        <p>No reservation or order request was created.</p>
    <?php endif; ?>

    <p>Reserved: <?= htmlspecialchars($reservationQty, ENT_QUOTES) ?></p>
    <p>Order request submitted: <?= htmlspecialchars($orderQty, ENT_QUOTES) ?></p>
    <a class="button" href="<?= BASE_URL ?>user/make_reservation.php">Make another reservation</a>
    <?php if ($reserveCents > 0): ?>
        <a class="button" href="<?= BASE_URL ?>user/reservations.php">View my reservations</a>
    <?php endif; ?>
    <a class="button" href="<?= BASE_URL ?>view_reagents.php">Back to View Reagents</a>
</main>
</body>
</html>
