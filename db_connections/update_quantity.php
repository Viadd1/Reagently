<?php
/**
 * Company-scoped admin stock adjustment.
 * Lock lot first, check ACTIVE reservations, update physical stock,
 * and write an ADJUST inventory transaction atomically.
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
    exit('Invalid request token. Reload Manage Reagents and retry.');
}

require BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

$companyId = tenant_require_user($link);
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Company Admin permission required.');
}
$adminId = (int) $_SESSION['user_id'];

function back(string $key, string $message): void
{
    header('Location: ' . BASE_URL . 'admin/manage_reagents.php?' . $key . '=' . urlencode($message));
    exit;
}

$lotId = filter_var($_POST['lot_id'] ?? null, FILTER_VALIDATE_INT);
$newRaw = trim((string) ($_POST['new_quantity'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));
if ($note === '') {
    $note = 'Admin quantity adjustment';
}

if (!$lotId || $lotId <= 0 ||
    !preg_match('/^\d+(?:\.\d{1,2})?$/D', $newRaw) ||
    (float) $newRaw > 99999999.99 || strlen($note) > 2000) {
    back('error', 'Invalid lot, quantity or note. Use a nonnegative amount with up to two decimals.');
}
$newCents = (int) round(((float) $newRaw) * 100);
$newAmount = number_format($newCents / 100, 2, '.', '');

$link->begin_transaction();
try {
    // Lock the lot BEFORE reading ACTIVE reservations.
    // Company scope is enforced on both the reagent and storage location.
    $stmt = $link->prepare(
        'SELECT l.current_quantity
         FROM reagent_lots l
         JOIN reagents r ON r.reagent_id = l.reagent_id
         JOIN locations loc ON loc.location_id = l.location_id
         WHERE l.lot_id = ? AND r.company_id = ? AND loc.company_id = ?
           AND r.is_deleted = 0
         FOR UPDATE'
    );
    $stmt->bind_param('iii', $lotId, $companyId, $companyId);
    $stmt->execute();
    $lot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lot) {
        throw new DomainException('Lot not found in your company.');
    }
    $oldCents = (int) round(((float) $lot['current_quantity']) * 100);

    $stmt = $link->prepare(
        "SELECT COALESCE(SUM(quantity_reserved), 0) AS reserved
         FROM reservations WHERE lot_id = ? AND status = 'ACTIVE'"
    );
    $stmt->bind_param('i', $lotId);
    $stmt->execute();
    $totals = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $reservedCents = (int) round(((float) $totals['reserved']) * 100);
    if ($newCents < $reservedCents) {
        throw new DomainException('New physical quantity cannot be lower than active reservations.');
    }

    // Re-check tenant ownership in the UPDATE as defense in depth.
    $stmt = $link->prepare(
        'UPDATE reagent_lots l
         JOIN reagents r ON r.reagent_id = l.reagent_id
         JOIN locations loc ON loc.location_id = l.location_id
         SET l.current_quantity = ?
         WHERE l.lot_id = ? AND r.company_id = ? AND loc.company_id = ?
           AND r.is_deleted = 0'
    );
    $stmt->bind_param('siii', $newAmount, $lotId, $companyId, $companyId);
    $stmt->execute();
    $stmt->close();

    $deltaAmount = number_format(($newCents - $oldCents) / 100, 2, '.', '');
    $stmt = $link->prepare(
        "INSERT INTO inventory_transactions
         (lot_id, user_id, transaction_type, quantity_change, note)
         VALUES (?, ?, 'ADJUST', ?, ?)"
    );
    $stmt->bind_param('iiss', $lotId, $adminId, $deltaAmount, $note);
    $stmt->execute();
    $stmt->close();

    $link->commit();
} catch (Throwable $e) {
    $link->rollback();
    if ($e instanceof DomainException) {
        back('error', $e->getMessage());
    }
    error_log('Reagently stock adjustment failed: ' . $e->getMessage());
    back('error', 'Could not update quantity. No changes were saved.');
}

$link->close();
back('msg', 'Quantity updated.');
