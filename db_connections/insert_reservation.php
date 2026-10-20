<?php
/**
 * Reservation preview only. No database writes are made here.
 * Availability is recalculated inside a transaction on confirmation.
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
require BASE_PATH . '/db.php';

// Company Data Isolation
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_user($link);

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
    exit('Invalid reservation details. Enter a positive quantity (up to two decimal places) and a valid date.');
}
$requestedCents = (int) round(((float) $requestedRaw) * 100);

$stmt = $link->prepare(
    'SELECT 1 FROM project_members pm
     JOIN projects p ON pm.project_id = p.project_id
     WHERE pm.project_id = ? AND pm.user_id = ? AND p.company_id = ?');


$stmt->bind_param('iii', $projectId, $userId, $companyId);
$stmt->execute();
$isMember = $stmt->get_result()->num_rows === 1;
$stmt->close();
if (!$isMember) {
    http_response_code(403);
    exit('You are not a member of this project.');
}

// A plain SELECT is intentional: a preview should never hold a row lock
// while a human is deciding whether to proceed.
$stmt = $link->prepare(
    'SELECT l.current_quantity FROM reagent_lots l
     JOIN reagents r ON r.reagent_id = l.reagent_id
     JOIN locations loc ON loc.location_id = l.location_id
     WHERE l.lot_id = ? AND r.company_id = ? AND loc.company_id = ? AND r.is_deleted = 0'
);
$stmt->bind_param('iii', $lotId, $companyId, $companyId);
$stmt->execute();
$lot = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$lot) {
    http_response_code(404);
    exit('Selected reagent lot does not exist.');
}

$stmt = $link->prepare(
    "SELECT COALESCE(SUM(quantity_reserved), 0) AS reserved
     FROM reservations WHERE lot_id = ? AND status = 'ACTIVE'"
);
$stmt->bind_param('i', $lotId);
$stmt->execute();
$reserved = $stmt->get_result()->fetch_assoc();
$stmt->close();
// Keep the DB connection available for navbar.php if it needs it.

$physicalCents = (int) round(((float) $lot['current_quantity']) * 100);
$reservedCents = (int) round(((float) $reserved['reserved']) * 100);
$availableCents = max(0, $physicalCents - $reservedCents);
$reserveCents = min($requestedCents, $availableCents);
$orderCents = $requestedCents - $reserveCents;
$formatQuantity = static fn(int $cents): string => number_format($cents / 100, 2, '.', '');
// Each preview gets a single-use confirmation token. A second submission cannot
// create another reservation if the user double-clicks or refreshes the result.
if (!isset($_SESSION['reservation_confirmations']) || !is_array($_SESSION['reservation_confirmations'])) {
    $_SESSION['reservation_confirmations'] = [];
}
if (count($_SESSION['reservation_confirmations']) >= 20) {
    $_SESSION['reservation_confirmations'] = array_slice($_SESSION['reservation_confirmations'], -19, null, true);
}
$confirmationToken = bin2hex(random_bytes(32));
$_SESSION['reservation_confirmations'][$confirmationToken] = hash('sha256', implode('|', [
    $projectId, $lotId, $requestedCents, $neededDate
]));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Review Reservation</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <h2>Review Reservation</h2>
    <p>Requested: <?= $formatQuantity($requestedCents) ?></p>
    <p>Available right now: <?= $formatQuantity($availableCents) ?></p>
    <p>Proposed reservation: <?= $formatQuantity($reserveCents) ?></p>
    <p>Proposed order request: <?= $formatQuantity($orderCents) ?></p>
    <p><strong>Preview only:</strong> Stock may change. Quantities will be checked again when you confirm.</p>
    <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>db_connections/confirm_partial_reservation.php">
        <input type="hidden" name="confirmation_token" value="<?= htmlspecialchars($confirmationToken, ENT_QUOTES) ?>">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">
        <input type="hidden" name="lot_id" value="<?= $lotId ?>">
        <input type="hidden" name="quantity_reserved" value="<?= $formatQuantity($requestedCents) ?>">
        <input type="hidden" name="needed_date" value="<?= htmlspecialchars($neededDate, ENT_QUOTES) ?>">
        <button class="button" type="submit" name="decision" value="confirm">Yes, continue</button>
        <button class="button" type="submit" name="decision" value="cancel">No, cancel</button>
    </form>
</main>
</body>
</html>
