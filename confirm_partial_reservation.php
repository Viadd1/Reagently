<?php

/*
Confirms a reservation after the user has been shown
the available quantity.

If the user chooses "cancel", nothing is saved.

If the user chooses "confirm":
- Check project membership again
- Check the reagent lot again
- Calculate available quantity again
- Create the reservation
- Create an order request if necessary
- Commit the transaction
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
$user_id =
    $_SESSION['user_id'];


// Get information from the confirmation form

$project_id =
    (int) $_POST['project_id'];

$lot_id =
    (int) $_POST['lot_id'];

$quantity_reserved =
    (float) $_POST['quantity_reserved'];

$needed_date =
    $_POST['needed_date'];

$decision =
    $_POST['decision'];


// If the user chose "No",
// do not create anything
if ($decision === 'cancel') {

    header("Location: make_reservation.php");
    exit;
}


/* =========================
   Start database transaction
   ========================= */

// We start a new transaction because
// the previous request has already ended.
//
// The stock must be checked again because
// the available quantity could have changed
// while the user was deciding.

$link->begin_transaction();


/* =========================
   Check project membership
   ========================= */

// Check that the current user still belongs
// to the selected project.

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


// If the user is not a member,
// cancel the transaction
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

// Get:
// - current physical quantity
// - reagent_id
// - reagent_name
//
// FOR UPDATE locks this lot while
// we perform the reservation.
// for reagent name we use an alias because the name is saves as name in reagent_lots
// and as reagent_name in order, making a little more convenitent to have the same name here

$lot_sql =
"SELECT

    reagent_lots.current_quantity,
    reagent_lots.reagent_id,
    reagents.name AS reagent_name

 FROM reagent_lots

 JOIN reagents
 ON reagents.reagent_id =
    reagent_lots.reagent_id

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


// Check that the selected lot exists
if ($lot_result->num_rows !== 1) {

    $link->rollback();

    die(
        "Selected reagent lot does not exist."
    );
}


// Get information about the lot
$lot =
    $lot_result->fetch_assoc();


$current_quantity =
    (float) $lot['current_quantity'];


$reagent_id =
    (int) $lot['reagent_id'];


$reagent_name =
    $lot['reagent_name'];


$lot_stmt->close();


/* =========================
   Calculate active reservations
   ========================= */

// Calculate how much of this lot
// is already reserved.

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

// Available quantity:
//
// Physical stock
// minus
// Active reservations

$available_quantity =
    $current_quantity
    -
    $reserved_quantity;


/* =========================
   Calculate reservation
   ========================= */

// Reserve as much as is available,
// but never more than the user requested.

$reservation_quantity =
    min(
        $quantity_reserved,
        $available_quantity
    );


// Anything that cannot be reserved
// becomes an order request.

$request_quantity =
    $quantity_reserved
    -
    $reservation_quantity;


/* =========================
   Insert reservation
   ========================= */

if ($reservation_quantity > 0) {

    $insert_reservation_sql =
    "INSERT INTO reservations
    (
        user_id,
        project_id,
        lot_id,
        quantity_reserved,
        needed_date
    )

    VALUES (?, ?, ?, ?, ?)";


    $insert_reservation_stmt =
        $link->prepare(
            $insert_reservation_sql
        );


    $insert_reservation_stmt->bind_param(
        "iiids",
        $user_id,
        $project_id,
        $lot_id,
        $reservation_quantity,
        $needed_date
    );


    if (
        !$insert_reservation_stmt->execute()
    ) {

        $link->rollback();

        die(
            "Could not create reservation: "
            .
            $insert_reservation_stmt->error
        );
    }


    $insert_reservation_stmt->close();
}


/* =========================
   Insert order request
   ========================= */

// Only create an order request
// if the user requested more than
// what was available.

if ($request_quantity > 0) {

    /*
    information needed for order requests is: s
    - user_id
    - reagent_id
    - reagent_name
    - request_text
    */

    $request_text =
        "Please order "
        .
        $request_quantity
        .
        " of "
        .
        $reagent_name
        .
        ". Needed by "
        .
        $needed_date
        .
        ".";


    $insert_request_sql =
    "INSERT INTO order_requests
    (
        user_id,
        reagent_id,
        reagent_name,
        request_text
    )

    VALUES (?, ?, ?, ?)";


    $insert_request_stmt =
        $link->prepare(
            $insert_request_sql
        );


    $insert_request_stmt->bind_param(
        "iiss",
        $user_id,
        $reagent_id,
        $reagent_name,
        $request_text
    );


    if (
        !$insert_request_stmt->execute()
    ) {

        $link->rollback();

        die(
            "Could not create order request: "
            .
            $insert_request_stmt->error
        );
    }


    $insert_request_stmt->close();
}


/* =========================
   Finish transaction
   ========================= */

// Everything succeeded,
// so permanently save the changes.

$link->commit();

/* =========================
   Show result
   ========================= */
?>

<!DOCTYPE html>

<html>

<head>

    <title>Reservations </title>

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

echo
    "Your request was completed.";

echo
    "<br><br>";

echo
    "Reserved: "
    .
    htmlspecialchars(
        $reservation_quantity
    );

echo
    "<br>";

echo
    "Requested from supplier: "
    .
    htmlspecialchars(
        $request_quantity
    );

echo
    "<br><br>";


echo
    '<a href="make_reservation.php">
        Make another reservation
    </a>';

echo
    "<br>";

echo
    '<a href="user.php">
        View my reservations
    </a>';

echo
    "<br>";

echo
    '<a href="index.php">
        Go to Home page
    </a>';


$link->close();

?>
