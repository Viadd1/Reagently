<?php

/*
Displays inventory transaction history.
Regular users can view their own transactions, while admins can view all recorded inventory changes.
*/



// Start the session so that we can identify
// the currently logged-in user
session_start();


// User must be logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


// Include database connection
include 'db.php';


// Get information about the current user
$user_id =
    $_SESSION['user_id'];

$role =
    $_SESSION['role'];



/* =========================
   SQL query
   ========================= */


// Admin users can see all inventory transactions.
//
// Regular users can only see transactions
// that were created by themselves.

if ($role === 'admin') {


    $sql =
    "SELECT

        inventory_transactions.transaction_id,
        inventory_transactions.transaction_type,
        inventory_transactions.quantity_change,
        inventory_transactions.note,
        inventory_transactions.created_at,

        users.username,

        reagents.name AS reagent_name,
        reagents.catalog_number,
        reagents.unit,

        reagent_lots.lot_number,

        projects.project_name

    FROM inventory_transactions


    JOIN users

    ON inventory_transactions.user_id
    =
    users.user_id


    JOIN reagent_lots

    ON inventory_transactions.lot_id
    =
    reagent_lots.lot_id


    JOIN reagents

    ON reagent_lots.reagent_id
    =
    reagents.reagent_id


    LEFT JOIN reservations

    ON inventory_transactions.reservation_id
    =
    reservations.reservation_id


    LEFT JOIN projects

    ON reservations.project_id
    =
    projects.project_id


    ORDER BY inventory_transactions.created_at DESC";


    // Admin query has no parameters
    $stmt =
        $link->prepare($sql);


} else {


    $sql =
    "SELECT

        inventory_transactions.transaction_id,
        inventory_transactions.transaction_type,
        inventory_transactions.quantity_change,
        inventory_transactions.note,
        inventory_transactions.created_at,

        users.username,

        reagents.name AS reagent_name,
        reagents.catalog_number,
        reagents.unit,

        reagent_lots.lot_number,

        projects.project_name

    FROM inventory_transactions


    JOIN users

    ON inventory_transactions.user_id
    =
    users.user_id


    JOIN reagent_lots

    ON inventory_transactions.lot_id
    =
    reagent_lots.lot_id


    JOIN reagents

    ON reagent_lots.reagent_id
    =
    reagents.reagent_id


    LEFT JOIN reservations

    ON inventory_transactions.reservation_id
    =
    reservations.reservation_id


    LEFT JOIN projects

    ON reservations.project_id
    =
    projects.project_id


    WHERE inventory_transactions.user_id = ?


    ORDER BY inventory_transactions.created_at DESC";


    // Prepare query for regular user
    $stmt =
        $link->prepare($sql);


    // user_id is an integer
    $stmt->bind_param(
        "i",
        $user_id
    );

}


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
        Inventory History - Reagently
    </title>

</head>


<body>


<h1>Inventory History</h1>


<?php

// Explain what the current user can see

if ($role === 'admin') {

    echo
        "<p>
            Showing all inventory transactions.
        </p>";

} else {

    echo
        "<p>
            Showing your inventory transactions.
        </p>";
}

?>


<!-- Inventory transaction table -->
<table border="1">


<tr>

    <th>Date</th>

    <th>Reagent</th>

    <th>Catalog No.</th>

    <th>Lot</th>

    <th>User</th>

    <th>Project</th>

    <th>Type</th>

    <th>Quantity Change</th>

    <th>Note</th>

</tr>


<?php


// Check whether any transactions exist
if ($result->num_rows > 0) {


    // Display each transaction
    while (
        $row =
        $result->fetch_assoc()
    ) {


        echo "<tr>";


        // Transaction date and time
        echo "<td>" .
        htmlspecialchars(
            $row["created_at"]
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


        // Lot number
        echo "<td>" .
        htmlspecialchars(
            $row["lot_number"] ?? ""
        ) .
        "</td>";


        // User who caused the inventory change
        echo "<td>" .
        htmlspecialchars(
            $row["username"]
        ) .
        "</td>";


        // Project name
        //
        // RECEIVE, DISCARD or ADJUST transactions
        // may not have a reservation or project.
        echo "<td>" .
        htmlspecialchars(
            $row["project_name"] ?? "-"
        ) .
        "</td>";


        // Transaction type
        //
        // Examples:
        // USAGE
        // RECEIVE
        // DISCARD
        // ADJUST
        echo "<td>" .
        htmlspecialchars(
            $row["transaction_type"]
        ) .
        "</td>";


        // Quantity change
        //
        // Examples:
        // +100 mL
        // -6 mL
        echo "<td>";


        // Add a + sign for positive inventory changes
        if (
            $row["quantity_change"] > 0
        ) {

            echo "+";

        }


        echo
            htmlspecialchars(
                $row["quantity_change"]
            ) .
            " " .
            htmlspecialchars(
                $row["unit"]
            );


        echo "</td>";


        // Optional transaction note
        echo "<td>" .
        htmlspecialchars(
            $row["note"] ?? ""
        ) .
        "</td>";


        echo "</tr>";

    }


} else {


    // No transaction history exists
    echo
    "<tr>

        <td colspan='9'>
            No inventory transactions found.
        </td>

    </tr>";

}


?>


</table>


<br><br>


<a href="view_reagents.php">
View reagents
</a>


<br><br>


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