<!DOCTYPE html>

<html>

<head>

    <title>Reservations</title>

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

<?php

/*
Validates and creates a new reagent reservation.
It checks project membership and current stock availability before saving the reservation to the database.
*/


// Start the session so that we can identify
// the currently logged-in user
session_start();


// If the user is not logged in,
// redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


// Include database connection
include 'db.php';


// Get the current logged-in user's ID
//
// IMPORTANT:
// user_id comes from the session,
// NOT from the reservation form.
//
// This prevents a user from creating a reservation
// using another user's ID.
$user_id =
    $_SESSION['user_id'];


// Get reservation information submitted
// from make_reservation.php

$project_id =
    (int) $_POST['project_id'];

$lot_id =
    (int) $_POST['lot_id'];

$quantity_reserved =
    (float) $_POST['quantity_reserved'];

$needed_date =
    $_POST['needed_date'];


// Check that the reservation quantity is positive
if ($quantity_reserved <= 0) {

    die(
        "Reservation quantity must be greater than 0."
    );
}

// IMPORTANT: this file now only checks if we want to reserve and order
// the actual orer and reserve is done by confirm_partial_reservation
// that is why we don't have a database link in this script

/* =========================
   Check project membership
   ========================= */

// Check whether the current user really belongs
// to the selected project.
//
// Even though make_reservation.php only shows
// the user's own projects, we must check again
// on the server side.
//
// Users should never be trusted only based
// on form values.

$membership_sql =
"SELECT
    project_id

 FROM project_members

 WHERE project_id = ?
 AND user_id = ?";


$membership_stmt =
    $link->prepare($membership_sql);


$membership_stmt->bind_param(
    "ii",
    $project_id,
    $user_id
);


$membership_stmt->execute();


$membership_result =
    $membership_stmt->get_result();


// If no matching project membership exists,
// stop the reservation
if ($membership_result->num_rows !== 1) {

    $link->rollback();

    die(
        "You are not a member of this project."
    );
}


$membership_stmt->close();



/* =========================
   Lock and check reagent lot
   ========================= */

// Get the current physical quantity
// of the selected reagent lot.
//
// FOR UPDATE locks this lot while the reservation
// is being checked and inserted.
//
// This helps prevent two users from reserving
// the same available stock at exactly the same time.

$lot_sql =
"SELECT
    reagent_lots.current_quantity

 FROM reagent_lots

 JOIN reagents
 ON reagents.reagent_id = reagent_lots.reagent_id

 WHERE reagent_lots.lot_id = ?
 AND reagents.is_deleted = 0

 FOR UPDATE";


$lot_stmt =
    $link->prepare($lot_sql);


$lot_stmt->bind_param(
    "i",
    $lot_id
);


$lot_stmt->execute();


$lot_result =
    $lot_stmt->get_result();


// Check whether the selected lot actually exists
if ($lot_result->num_rows !== 1) {

    $link->rollback();

    die(
        "Selected reagent lot does not exist."
    );
}


// Get physical stock quantity
$lot =
    $lot_result->fetch_assoc();


$current_quantity =
    (float) $lot['current_quantity'];


$lot_stmt->close();



/* =========================
   Calculate reserved quantity
   ========================= */

// Calculate the total quantity already reserved
// for this lot.
//
// IMPORTANT:
// This includes ALL ACTIVE reservations,
// regardless of user or project.
//
// This is necessary because the physical reagent
// inventory is shared by all projects.

$reserved_sql =
"SELECT

    COALESCE(
        SUM(quantity_reserved),
        0
    ) AS reserved_quantity

 FROM reservations

 WHERE lot_id = ?

 AND status = 'ACTIVE'";


$reserved_stmt =
    $link->prepare($reserved_sql);


$reserved_stmt->bind_param(
    "i",
    $lot_id
);


$reserved_stmt->execute();


$reserved_result =
    $reserved_stmt->get_result();


$reserved =
    $reserved_result->fetch_assoc();


$reserved_quantity =
    (float) $reserved['reserved_quantity'];


$reserved_stmt->close();



/* =========================
   Calculate available quantity
   ========================= */

// Available quantity is calculated dynamically.
//
// Available
// = Physical stock - Active reservations

$available_quantity =
    $current_quantity
    -
    $reserved_quantity;



/* =========================
   Check requested quantity
   ========================= */

// Reserve as much as is currently available, 
// but never more than the user requested. 

$reservation_quantity = 
    min( 
        $quantity_reserved, 
        $available_quantity 
    ); 

// Anything that cannot be reserved becomes 
// an order request. 

$request_quantity = 
    $quantity_reserved - $reservation_quantity;

/* =========================
   HTML form
   ========================= */

// display to the user what the stock is and ask for an input Yes or No
// if yes, a reservation and order will be made
// if not, reservation is canceled

?>

<!DOCTYPE html>

<html>

<form method="POST" action="confirm_partial_reservation.php">

    <input type="hidden" name="project_id"
           value="<?= htmlspecialchars($project_id) ?>">

    <input type="hidden" name="lot_id"
           value="<?= htmlspecialchars($lot_id) ?>">

    <input type="hidden" name="quantity_reserved"
           value="<?= htmlspecialchars($quantity_reserved) ?>">

    <input type="hidden" name="needed_date"
           value="<?= htmlspecialchars($needed_date) ?>">

    <p>
        You requested:
        <?= htmlspecialchars($quantity_reserved) ?>
    </p>

    <p>
        Available quantity:
        <?= htmlspecialchars($available_quantity) ?>
    </p>

    <p>
        Quantity to reserve:
        <?= htmlspecialchars($reservation_quantity) ?>
    </p>

    <p>
        Quantity to order:
        <?= htmlspecialchars($request_quantity) ?>
    </p>

    
    <button type="submit" name="decision" value="confirm">
        Yes, continue
    </button>

    <button type="submit" name="decision" value="cancel">
        No, cancel
    </button>

</form>

</html>

<?php

// The reservation and order request will only be created by 
// confirm_partial_reservation.php after the user clicks "Yes". 

exit; 

?>