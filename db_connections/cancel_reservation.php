<?php

/*
Cancels an active reservation owned by the logged-in user.
Cancelling a reservation releases the reserved quantity without changing the physical stock.
*/


// Start session and set base path variable
require_once __DIR__ . "/../init.php";


// If the user is not logged in,
// redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /login.php");
    exit;
}


// Include database connection
include BASE_PATH . '/db.php';


// Get the currently logged-in user's ID
$user_id =
    $_SESSION['user_id'];


// Check whether reservation_id was provided
// in the URL
//
// Example:
// cancel_reservation.php?reservation_id=5

if (!isset($_GET['reservation_id'])) {

    die(
        "Reservation ID is missing."
    );
}


// Get reservation ID from the URL
$reservation_id =
    (int) $_GET['reservation_id'];


// Check that reservation ID is valid
if ($reservation_id <= 0) {

    die(
        "Invalid reservation ID."
    );
}



/* =========================
   Cancel reservation
   ========================= */


// Change reservation status from ACTIVE
// to CANCELLED.
//
// IMPORTANT:
//
// user_id = ?
// ensures that a user can only cancel
// their own reservation.
//
// status = 'ACTIVE'
// ensures that COMPLETED or already CANCELLED
// reservations cannot be cancelled again.

$sql =
"UPDATE reservations

 SET status = 'CANCELLED'

 WHERE reservation_id = ?

 AND user_id = ?

 AND status = 'ACTIVE'";


// Prepare SQL statement
$stmt =
    $link->prepare($sql);


// Bind variables
//
// reservation_id = integer
// user_id        = integer
//
// Therefore the type definition is "ii".

$stmt->bind_param(
    "ii",
    $reservation_id,
    $user_id
);


// Execute the UPDATE statement
$stmt->execute();

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Reservations</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >

</head>

<body>


<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

<?php
/* =========================
   Check result
   ========================= */


// affected_rows tells us how many rows
// were actually changed.
//
// If affected_rows = 1:
// the reservation was successfully cancelled.
//
// If affected_rows = 0:
// possible reasons include:
// - reservation does not exist
// - reservation belongs to another user
// - reservation is already CANCELLED
// - reservation is already COMPLETED

if ($stmt->affected_rows === 1) {


    echo
        "Reservation cancelled successfully.";


    echo
        "<br><br>";


    echo
        '<a class="button" href=' . BASE_URL . 'user/reservations.php>
            Back to reservations
        </a>';


} else {


    echo
        "The reservation could not be cancelled.";


    echo
        "<br><br>";


    echo
        "It may not exist, may belong to another user,
        or may no longer be active.";


    echo
        "<br><br>";


    echo
        '<a class="button" href=' . BASE_URL . 'user/reservations.php>
            Back to reservations
        </a>';

}

?>

</main>

</body>

</html>

<?php

// Close prepared statement
$stmt->close();


// Close database connection
$link->close();

?>