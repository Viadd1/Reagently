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

// Added possibility to link back to this reservation script for confirm partial request
// If user cancelled 
if ($decision === 'cancel') { 
    
    header("Location: make_reservation.php"); exit; 
}

/* =========================
   Start database transaction
   ========================= */

// A transaction keeps the availability check
// and reservation insertion together.
//
// Either everything succeeds,
// or nothing is saved.
$link->begin_transaction();



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

// Reject the reservation if the requested
// quantity is larger than the latest
// available quantity.

//added:
// Reserve as much as is currently available, 
// but never more than the user requested. 

$reservation_quantity = 
    min( 
        $quantity_requested, 
        $available_quantity 
    ); 

// Anything that cannot be reserved becomes 
// an order request. 

$request_quantity = 
    $quantity_requested - $reservation_quantity;
//end of added part, removed corresponding end of script "cancel database transaction" 

/* =========================
   Insert reservation
   ========================= */

// The availability check has passed.
//
// Create the reservation.
//
// status is not included because the database
// automatically uses the default value 'ACTIVE'.
//
// created_at is also generated automatically.

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
    $link->prepare($insert_reservation_sql);


// Data types:
//
// user_id            = integer
// project_id         = integer
// lot_id             = integer
// quantity_reserved  = decimal/double
// needed_date        = string
//
// Therefore:
// i i i d s

$insert_stmt->bind_param(
    "iiids",
    $user_id,
    $project_id,
    $lot_id,
    $quantity_reserved,
    $needed_date
);

if ( 
    !$insert_reservation_stmt->execute() 
) { 
    $link->rollback(); 
    
    die( "Could not create reservation: " 
    . 
    $insert_reservation_stmt->error 
    ); 
}

    $insert_reservation_stmt->close(); 
}

/* ========================= 
    Insert order request 
    ========================= */ 
    
if ($request_quantity > 0) { 

    $insert_request_sql = 
    "INSERT INTO order_requests 
    ( 
        user_id, 
        project_id, 
        lot_id, 
        quantity_requested, 
        needed_date 
    )

    VALUES (?, ?, ?, ?, ?)"; 
    
    $insert_request_stmt = 
        $link->prepare( 
            $insert_request_sql 
        );

    $insert_request_stmt->bind_param( 
        "iiids", 
        $user_id, 
        $project_id, 
        $lot_id, 
        $request_quantity, 
        $needed_date 
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

if ($result) {

    // Everything was successful,
    // so permanently save the transaction
    $link->commit();


    echo
        "Reservation created successfully.";

    echo
        "<br><br>";


    echo
        '<a href="make_reservation.php">
            Make another reservation
        </a>';

    echo
        "<br>";


    echo
        '<a href="view_reagents.php">
            View reagents
        </a>';

    echo
        "<br>";


    echo
        '<a href="user.php">
            View my reservations
        </a>';

} else {

    // Something failed,
    // so cancel all changes in this transaction
    $link->rollback();


    echo
        "Error: " .
        $insert_stmt->error;
}


// Close prepared statement
$insert_stmt->close();


// Close database connection
$link->close();



// new
$link->commit(); 

echo 
    "Reservation and order request created."; 
    
echo "<br><br>";

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

$link->close();
// end
?>