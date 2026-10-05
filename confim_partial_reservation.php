```php
<?php

session_start();


// Check login
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


include 'db.php';


$user_id =
    $_SESSION['user_id'];


// Get data from previous form

$project_id =
    (int) $_POST['project_id'];

$lot_id =
    (int) $_POST['lot_id'];

$quantity_requested =
    (float) $_POST['quantity_reserved'];

$needed_date =
    $_POST['needed_date'];

$decision =
    $_POST['decision'];


// If user cancelled
if ($decision === 'cancel') {

    header("Location: make_reservation.php");
    exit;
}



/* =========================
   Start transaction
   ========================= */

$link->begin_transaction();



/* =========================
   Check project membership
   ========================= */

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


if ($lot_result->num_rows !== 1) {

    $link->rollback();

    die(
        "Selected reagent lot does not exist."
    );
}


$lot =
    $lot_result->fetch_assoc();


$current_quantity =
    (float) $lot['current_quantity'];


$lot_stmt->close();



/* =========================
   Calculate active reservations
   ========================= */

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

$available_quantity =
    $current_quantity
    -
    $reserved_quantity;



/* =========================
   Calculate reservation/request
   ========================= */

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
    $quantity_requested
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

$link->commit();


echo
    "Reservation and order request created.";

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


$link->close();

?>

