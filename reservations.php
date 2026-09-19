<?php

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



/* =========================
   Get visible reservations
   ========================= */


// Retrieve reservations from projects that
// the current user belongs to.
//
// IMPORTANT:
//
// The user can see reservations made by other users,
// but only if those reservations belong to a project
// that the current user is also a member of.

$sql =
"SELECT

    reservations.reservation_id,
    reservations.user_id,
    reservations.quantity_reserved,
    reservations.needed_date,
    reservations.status,
    reservations.created_at,

    projects.project_name,

    users.username,

    reagents.name AS reagent_name,
    reagents.catalog_number,
    reagents.unit,

    reagent_lots.lot_number,
    reagent_lots.supplier

FROM reservations


-- Connect each reservation to its project
JOIN projects

ON reservations.project_id
=
projects.project_id


-- Check whether the currently logged-in user
-- belongs to the reservation's project
JOIN project_members

ON reservations.project_id
=
project_members.project_id


-- Get information about the user
-- who originally made the reservation
JOIN users

ON reservations.user_id
=
users.user_id


-- Get information about the selected lot
JOIN reagent_lots

ON reservations.lot_id
=
reagent_lots.lot_id


-- Get reagent information
JOIN reagents

ON reagent_lots.reagent_id
=
reagents.reagent_id


-- Only show reservations from projects
-- that the current user belongs to
WHERE project_members.user_id = ?


ORDER BY

    CASE
        WHEN reservations.status = 'ACTIVE'
        THEN 0
        ELSE 1
    END,

    reservations.needed_date,
    reservations.created_at DESC";


// Prepare SQL query
$stmt =
    $link->prepare($sql);


// Bind current user's ID
$stmt->bind_param(
    "i",
    $user_id
);


// Execute query
$stmt->execute();


// Get query result
$result =
    $stmt->get_result();

?>


<!DOCTYPE html>

<html>

<head>

    <title>
        Reservations - Reagently
    </title>

</head>


<body>


<h1>Reservations</h1>


<p>
    Reservations from your projects are shown below.
</p>


<!-- Reservation table -->
<table border="1">

<tr>

    <th>Project</th>

    <th>Reagent</th>

    <th>Catalog No.</th>

    <th>Supplier</th>

    <th>Lot</th>

    <th>Reserved By</th>

    <th>Quantity</th>

    <th>Needed Date</th>

    <th>Status</th>

    <th>Created</th>

    <th>Action</th>

</tr>


<?php


// Check whether any visible reservations exist
if ($result->num_rows > 0) {


    // Loop through all visible reservations
    while (
        $row =
        $result->fetch_assoc()
    ) {


        echo "<tr>";


        // Project name
        echo "<td>" .
        htmlspecialchars(
            $row["project_name"]
        ) .
        "</td>";


        // Reagent name
        echo "<td>" .
        htmlspecialchars(
            $row["reagent_name"]
        ) .
        "</td>";


        // Catalog number
        echo "<td>" .
        htmlspecialchars(
            $row["catalog_number"]
        ) .
        "</td>";


        // Supplier
        echo "<td>" .
        htmlspecialchars(
            $row["supplier"] ?? ""
        ) .
        "</td>";


        // Lot number
        echo "<td>" .
        htmlspecialchars(
            $row["lot_number"] ?? ""
        ) .
        "</td>";


        // User who made the reservation
        echo "<td>" .
        htmlspecialchars(
            $row["username"]
        ) .
        "</td>";


        // Reserved quantity + reagent unit
        echo "<td>" .
        htmlspecialchars(
            $row["quantity_reserved"]
        ) .
        " " .
        htmlspecialchars(
            $row["unit"]
        ) .
        "</td>";


        // Date when reagent is needed
        echo "<td>" .
        htmlspecialchars(
            $row["needed_date"]
        ) .
        "</td>";


        // Reservation status
        echo "<td>" .
        htmlspecialchars(
            $row["status"]
        ) .
        "</td>";


        // Reservation creation time
        echo "<td>" .
        htmlspecialchars(
            $row["created_at"]
        ) .
        "</td>";


        /* =========================
           Reservation actions
           ========================= */

        echo "<td>";


        // For now, only the user who created
        // the reservation can record usage
        // or cancel their own ACTIVE reservation.
        //
        // Other project members can see the
        // reservation information but cannot
        // change it.

        if (
            $row["status"] === "ACTIVE"
            &&
            (int) $row["user_id"] ===
            (int) $user_id
        ) {


            // Link to record the final usage
            echo
            "<a href='record_usage.php?reservation_id=" .
            $row["reservation_id"] .
            "'>
                Record usage
            </a>";


            echo "<br>";


            // Link to cancel the reservation
            echo
            "<a href='cancel_reservation.php?reservation_id=" .
            $row["reservation_id"] .
            "'>
                Cancel
            </a>";


        } else {


            // No actions are available for:
            // - COMPLETED reservations
            // - CANCELLED reservations
            // - reservations created by another user

            echo "-";

        }


        echo "</td>";


        echo "</tr>";

    }


} else {


    // No reservations are visible to this user
    echo
    "<tr>

        <td colspan='11'>
            No reservations found.
        </td>

    </tr>";

}


?>


</table>


<br><br>


<!-- Create a new reservation -->
<a href="make_reservation.php">
Make a reservation
</a>


<br><br>


<!-- Return to home page -->
<a href="index.php">
Back to home
</a>


</body>

</html>


<?php

// Close prepared statement
$stmt->close();


// Close database connection
$link->close();

?>