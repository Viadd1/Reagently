<?php

/*
Displays an active reservation and allows the user to enter the actual quantity of reagent used and an optional note.
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


// Get the currently logged-in user's ID
$user_id =
    $_SESSION['user_id'];


// Check whether reservation_id was provided
// in the URL
//
// Example:
// record_usage.php?reservation_id=5

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
   Get reservation information
   ========================= */


// Retrieve the reservation together with
// reagent and lot information.
//
// IMPORTANT:
//
// user_id = ?
// ensures that the logged-in user can only
// record usage for their own reservation.
//
// status = 'ACTIVE'
// ensures that usage cannot be recorded for
// an already completed or cancelled reservation.

$sql =
"SELECT

    reservations.reservation_id,
    reservations.quantity_reserved,
    reservations.needed_date,
    reservations.status,

    projects.project_name,

    reagents.name AS reagent_name,
    reagents.catalog_number,
    reagents.unit,

    reagent_lots.lot_number,
    reagent_lots.current_quantity

FROM reservations


JOIN projects

ON reservations.project_id
=
projects.project_id


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

AND reservations.status = 'ACTIVE'";


// Prepare SQL statement

$stmt =
    $link->prepare($sql);


// Bind variables
//
// reservation_id = integer
// user_id        = integer

$stmt->bind_param(
    "ii",
    $reservation_id,
    $user_id
);


// Execute query

$stmt->execute();


// Get result

$result =
    $stmt->get_result();



// Check whether exactly one valid ACTIVE
// reservation was found

if ($result->num_rows !== 1) {

    $stmt->close();
    $link->close();

    die(
        "Reservation not found or usage cannot be recorded."
    );
}


// Fetch reservation information

$reservation =
    $result->fetch_assoc();

?>


<!DOCTYPE html>

<html>

<head>

    <title>
        Record Usage - Reagently
    </title>

</head>


<body>


<h1>Record Usage</h1>


<!-- Display reservation information -->

<p>
    <strong>Project:</strong>

    <?php

    echo htmlspecialchars(
        $reservation["project_name"]
    );

    ?>

</p>


<p>
    <strong>Reagent:</strong>

    <?php

    echo htmlspecialchars(
        $reservation["reagent_name"]
    );

    ?>

</p>


<p>
    <strong>Catalog number:</strong>

    <?php

    echo htmlspecialchars(
        $reservation["catalog_number"]
    );

    ?>

</p>


<p>
    <strong>Lot:</strong>

    <?php

    echo htmlspecialchars(
        $reservation["lot_number"] ?? ""
    );

    ?>

</p>


<p>
    <strong>Reserved quantity:</strong>

    <?php

    echo htmlspecialchars(
        $reservation["quantity_reserved"]
    );

    echo " ";

    echo htmlspecialchars(
        $reservation["unit"]
    );

    ?>

</p>


<p>
    <strong>Current physical stock:</strong>

    <?php

    echo htmlspecialchars(
        $reservation["current_quantity"]
    );

    echo " ";

    echo htmlspecialchars(
        $reservation["unit"]
    );

    ?>

</p>


<p>
    <strong>Needed date:</strong>

    <?php

    echo htmlspecialchars(
        $reservation["needed_date"]
    );

    ?>

</p>



<!-- =========================
     Usage form
     ========================= -->


<form
    action="insert_usage.php"
    method="POST"
>


    <!--
        Hidden reservation ID

        The user does not need to type this value,
        but insert_usage.php needs to know which
        reservation is being completed.
    -->
    <input
        type="hidden"
        name="reservation_id"
        value="<?php
            echo htmlspecialchars(
                $reservation["reservation_id"]
            );
        ?>"
    >


    <!--
        Actual quantity used

        This can be smaller than the reserved quantity.

        Example:

        Reserved = 10 mL
        Used     = 6 mL

        The unused 4 mL is automatically released
        when the reservation becomes COMPLETED.
    -->
    Actual quantity used:

    <input
        type="number"
        name="quantity_used"
        min="0"
        max="<?php
            echo htmlspecialchars(
                $reservation["quantity_reserved"]
            );
        ?>"
        step="0.01"
        required
    >


    <?php

    echo " " .
    htmlspecialchars(
        $reservation["unit"]
    );

    ?>


    <br><br>


    <!-- Optional note about the usage -->
    Note:

    <br>

    <textarea
        name="note"
        rows="4"
        cols="40"
    ></textarea>


    <br><br>


    <!-- Submit usage -->
    <input
        type="submit"
        value="Record Usage"
    >


</form>


<br>


<a href="reservations.php">
Back to reservations
</a>


</body>

</html>


<?php

// Close prepared statement
$stmt->close();


// Close database connection
$link->close();

?>