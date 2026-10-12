<!-- Insert Usage -->
<!-- Records the final reagent usage for a reservation.
It updates the physical stock, creates an inventory transaction, and changes the reservation status to COMPLETED. -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
include BASE_PATH . '/db.php';

// Get the currently logged-in user's ID.
// IMPORTANT:
// user_id comes from the session, not from the form.
$user_id =
    $_SESSION['user_id'];

// Get data submitted from record_usage.php
$reservation_id =
    (int) $_POST['reservation_id'];

$quantity_used =
    (float) $_POST['quantity_used'];

$note =
    $_POST['note'] ?? '';

?>

<!DOCTYPE html>
<html>
<head>

    <title>Reagently - Insert Usage</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >

</head>

<?php

// Basic validation
// quantity_used may be 0, but it cannot be negative.
if ($quantity_used < 0) {

    die(
        "Usage quantity cannot be negative."
    );
}

/* Start database transaction */

// The following operations belong together:
// 1. Update physical stock
// 2. Create usage transaction
// 3. Complete reservation

// If one step fails, all changes should be cancelled.

$link->begin_transaction();

/* Get and lock reservation */

// Retrieve the ACTIVE reservation.

// We also retrieve lot information because physical stock is stored in reagent_lots.
// IMPORTANT:
// user_id = ?
// ensures that users can only record usage for their own reservations.
// status = 'ACTIVE'
// ensures that the same reservation cannot be completed twice.
// FOR UPDATE locks the reservation and lot while the usage is being processed.

$reservation_sql =
"SELECT

    reservations.reservation_id,
    reservations.lot_id,
    reservations.quantity_reserved,

    reagent_lots.current_quantity,

    reagents.unit

FROM reservations

JOIN reagent_lots

ON reservations.lot_id
=
reagent_lots.lot_id

JOIN reagents

ON reagent_lots.reagent_id
=
reagents.reagent_id

WHERE reservations.reservation_id = ?

AND reservations.user_id = ?

AND reservations.status = 'ACTIVE'

FOR UPDATE";

// Prepare query
$reservation_stmt =
    $link->prepare($reservation_sql);

// Bind IDs
$reservation_stmt->bind_param(
    "ii",
    $reservation_id,
    $user_id
);

// Execute
$reservation_stmt->execute();

// Get result
$reservation_result =
    $reservation_stmt->get_result();

// The reservation must exist, belong to the current user, and still be ACTIVE.
if ($reservation_result->num_rows !== 1) {

    $link->rollback();

    $reservation_stmt->close();

    $link->close();

    die(
        "Reservation not found or usage cannot be recorded."
    );
}

// Fetch reservation information
$reservation =
    $reservation_result->fetch_assoc();

// Get important values
$lot_id =
    (int) $reservation['lot_id'];

$quantity_reserved =
    (float) $reservation['quantity_reserved'];

$current_quantity =
    (float) $reservation['current_quantity'];

$unit =
    $reservation['unit'];

$reservation_stmt->close();

/* Validate actual usage */

// Actual usage cannot be larger than the originally reserved quantity.
// Example:
// Reserved = 10 mL
// Used = 6 mL   -> allowed
// Used = 10 mL  -> allowed
// Used = 12 mL  -> NOT allowed

if (
    $quantity_used
    >
    $quantity_reserved
) {

    $link->rollback();

    $link->close();

    echo
        "Usage quantity cannot be larger than the reserved quantity.";

    echo "<br><br>";

    echo
        "Reserved quantity: " .
        htmlspecialchars(
            $quantity_reserved
        ) .
        " " .
        htmlspecialchars(
            $unit
        );

    echo "<br><br>";

    echo
        '<a class="button" href=' . BASE_URL .'user/reservations.php>
            Back to reservations
        </a>';

    exit;
}

// Also check physical stock.
// Normally there should be enough stock, but stock may have changed because of an adjustment or other inventory operation.

if (
    $quantity_used
    >
    $current_quantity
) {

    $link->rollback();

    $link->close();

    echo
        "Not enough physical stock.";

    echo "<br><br>";

    echo
        "Current physical stock: " .
        htmlspecialchars(
            $current_quantity
        ) .
        " " .
        htmlspecialchars(
            $unit
        );

    echo "<br><br>";

    echo
        '<a class="button" href=' . BASE_URL . 'user/reservations.php>
            Back to reservations
        </a>';

    exit;
}

/* Update physical stock */

// Physical stock is reduced ONLY by the quantity actually used.
// Example:
// Physical = 100 mL
// Reserved = 10 mL
// Used     = 6 mL
// New physical stock:
// 100 - 6 = 94 mL

$stock_sql =
"UPDATE reagent_lots

 SET current_quantity =
     current_quantity - ?

 WHERE lot_id = ?";

$stock_stmt =
    $link->prepare($stock_sql);

// quantity_used = decimal
// lot_id        = integer
// Therefore: "di"

$stock_stmt->bind_param(
    "di",
    $quantity_used,
    $lot_id
);

// Execute stock update
if (!$stock_stmt->execute()) {

    $link->rollback();

    die(
        "Failed to update physical stock."
    );
}

$stock_stmt->close();

/* Create inventory transaction */

// Store the physical stock change in inventory_transactions.
// USAGE is a negative change.
// Example:
// quantity_used = 6
// quantity_change = -6

$transaction_type =
    "USAGE";

$quantity_change =
    -$quantity_used;

// Insert usage history
$transaction_sql =
"INSERT INTO inventory_transactions
(
    lot_id,
    user_id,
    reservation_id,
    transaction_type,
    quantity_change,
    note
)

VALUES (?, ?, ?, ?, ?, ?)";

$transaction_stmt =
    $link->prepare($transaction_sql);

// Data types:
// lot_id            = integer
// user_id           = integer
// reservation_id    = integer
// transaction_type  = string
// quantity_change   = decimal
// note              = string
// Therefore: i i i s d s

$transaction_stmt->bind_param(
    "iiisds",
    $lot_id,
    $user_id,
    $reservation_id,
    $transaction_type,
    $quantity_change,
    $note
);

// Execute transaction INSERT
if (!$transaction_stmt->execute()) {

    $link->rollback();

    die(
        "Failed to create inventory transaction."
    );
}

$transaction_stmt->close();

/* Complete reservation */

// The reservation has now been used.
// Change its status:
// ACTIVE
//   ↓
// COMPLETED
// Once completed, the entire reserved quantity is released from the reservation system.
// Any amount that was reserved but not used simply becomes available again.

$complete_sql =
"UPDATE reservations

 SET status = 'COMPLETED'

 WHERE reservation_id = ?

 AND user_id = ?

 AND status = 'ACTIVE'";

$complete_stmt =
    $link->prepare($complete_sql);

$complete_stmt->bind_param(
    "ii",
    $reservation_id,
    $user_id
);

// Execute status update
if (!$complete_stmt->execute()) {

    $link->rollback();

    die(
        "Failed to complete reservation."
    );
}

// The reservation should have changed exactly once
if ($complete_stmt->affected_rows !== 1) {

    $link->rollback();

    die(
        "Reservation could not be completed."
    );
}

$complete_stmt->close();

/* Commit transaction */

// All three operations were successful:
// physical stock updated
// usage transaction created
// reservation completed

// Save all changes permanently.
$link->commit();

?>

<body>

<?php
// Main header and navigation bar
include "navbar.php";
?>

<main class="page">

<?php

/* Success message */
echo
    "Usage recorded successfully.";

echo
    "<br><br>";

echo
    "Actual quantity used: " .
    htmlspecialchars(
        $quantity_used
    ) .
    " " .
    htmlspecialchars(
        $unit
    );

echo
    "<br><br>";

echo
    '<a class="button" href=' . BASE_URL . 'user/reservations.php>
        Back to reservations
    </a>';

echo
    "<br>";

?>

</main>
</body>
</html>

<?php

// Close database connection
$link->close();

?>