<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$lot_id       = (int)$_POST['lot_id'];
$new_quantity = (float)$_POST['new_quantity'];
$note         = trim($_POST['note'] ?? '') ?: 'Admin quantity adjustment';
$admin_id     = $_SESSION['user_id'];

function back($key, $text) {
    header("Location: manage_reagents.php?$key=" . urlencode($text));
    exit;
}

if ($new_quantity < 0) { back('error', 'Quantity cannot be negative.'); }

$link->begin_transaction();

try {
    // Lock the lot
    $stmt = $link->prepare("SELECT current_quantity FROM reagent_lots WHERE lot_id = ? FOR UPDATE");
    $stmt->bind_param("i", $lot_id);
    $stmt->execute();
    $lot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lot) { $link->rollback(); back('error', 'Lot not found.'); }

    $old_quantity = (float)$lot['current_quantity'];

    // Don't allow going below what is already reserved
    $stmt = $link->prepare(
        "SELECT COALESCE(SUM(quantity_reserved), 0) AS reserved
         FROM reservations WHERE lot_id = ? AND status = 'ACTIVE'");
    $stmt->bind_param("i", $lot_id);
    $stmt->execute();
    $reserved = (float)$stmt->get_result()->fetch_assoc()['reserved'];
    $stmt->close();

    if ($new_quantity < $reserved) {
        $link->rollback();
        back('error', "Cannot set quantity below the active reservations ($reserved).");
    }

    // Update the lot
    $stmt = $link->prepare("UPDATE reagent_lots SET current_quantity = ? WHERE lot_id = ?");
    $stmt->bind_param("di", $new_quantity, $lot_id);
    $stmt->execute();
    $stmt->close();

    // Log it in the inventory history
    $change = $new_quantity - $old_quantity;
    $stmt = $link->prepare(
        "INSERT INTO inventory_transactions
         (lot_id, user_id, transaction_type, quantity_change, note)
         VALUES (?, ?, 'ADJUST', ?, ?)");
    $stmt->bind_param("iids", $lot_id, $admin_id, $change, $note);
    $stmt->execute();
    $stmt->close();

    $link->commit();
} catch (mysqli_sql_exception $e) {
    $link->rollback();
    back('error', 'Database error.');
}

$link->close();
back('msg', 'Quantity updated.');