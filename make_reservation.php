<!-- Displays the reservation form for the logged-in user.
Only projects the user belongs to are shown, and reagent availability is calculated from the current stock and active reservations. -->

<?php
// Start the session so that we can identify the currently logged-in user
session_start();


// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


// Include database connection
include 'db.php';


// Get the ID of the currently logged-in user
$user_id =
    $_SESSION['user_id'];



//Get user's projects


// Only show projects that the current user belongs to. project_members connects users and projects.

$project_sql =
"SELECT
    projects.project_id,
    projects.project_name

 FROM projects

 JOIN project_members ON projects.project_id = project_members.project_id

 WHERE project_members.user_id = ?

 ORDER BY projects.project_name";


// Prepare the query

$project_stmt = $link->prepare($project_sql);


// Bind the current user's ID

$project_stmt->bind_param(
    "i",
    $user_id
);


// Execute the query

$project_stmt->execute();


// Get project query result

$project_result = $project_stmt->get_result();



//Get reagent lots


// This query retrieves all reagent lots together with their available quantity.
// Physical quantity = reagent_lots.current_quantity
// Reserved quantity = total quantity from all ACTIVE reservations
// Available quantity = Physical - Reserved
// Reserved quantity is calculated from ALL ACTIVE reservations, regardless of which project made them.

$lot_sql =
"SELECT

    reagent_lots.lot_id,

    reagents.name,
    reagents.catalog_number,
    reagents.unit,

    reagent_lots.lot_number,
    reagent_lots.current_quantity,
    reagent_lots.expiry_date,

    locations.room,
    locations.storage_unit,
    locations.shelf,
    locations.box_name,

    COALESCE(reservation_totals.reserved_quantity, 0) AS reserved_quantity,

    reagent_lots.current_quantity - COALESCE(reservation_totals.reserved_quantity,0) AS available_quantity


FROM reagent_lots
JOIN reagents ON reagent_lots.reagent_id = reagents.reagent_id
JOIN locations ON reagent_lots.location_id = locations.location_id
LEFT JOIN

    SELECT
        lot_id,
        SUM(quantity_reserved)
        AS reserved_quantity

    FROM reservations

    WHERE status = 'ACTIVE'

    GROUP BY lot_id

) AS reservation_totals


ON reagent_lots.lot_id = reservation_totals.lot_id

WHERE

    reagents.is_deleted = 0 AND reagent_lots.current_quantity - COALESCE( reservation_totals.reserved_quantity, 0) > 0

ORDER BY

    reagents.name,
    reagent_lots.expiry_date";


// Execute the reagent lot query

$lot_result =
    $link->query($lot_sql);

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Make reservation</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<!-- Website title -->

<header>

    <h1>Reagently</h1>

</header>

<!-- Navigation bar -->

<nav class="navbar">

    <a href="index.php">
        Home
    </a>

    <?php

    // Show these links only when a user is logged in
    if (isset($_SESSION['user_id'])) {

    ?>

        <a href="view_reagents.php">
            View reagents
        </a>

        <a href="user.php">
            User
        </a>

        <?php

        // Only admins see the Admin link
        if (
            isset($_SESSION['role'])
            &&
            $_SESSION['role'] === 'admin'
        ) {

        ?>

            <a href="admin.php">
                Admin
            </a>

        <?php

        }

        ?>

        <a href="logout.php">
            Log out
        </a>

    <?php

    } else {

    ?>

        <!-- Show Login and Register when nobody is logged in -->

        <a href="login.php">
            Login
        </a>

        <a href="register.php">
            Register
        </a>

    <?php

    }

    ?>

</nav>

<main class="page">
    <h2>Make Reservation</h2>
    <h3>Reserve your reagents here!</h3>

<!--
    Reservation form

    The selected project, reagent lot,
    quantity and needed date will be sent
    to insert_reservation.php.
-->
<form
    action="insert_reservation.php"
    method="POST"
>


    <!--Project selection>

    Project:

    <select
        name="project_id"
        required
    >

        <?php

        // Check whether the user belongs to at least one project

        if ($project_result->num_rows > 0) {

            // Create one option for each project
            // that the current user belongs to

            while (
                $project =
                $project_result->fetch_assoc()
            ) {

                echo
                "<option value='" .
                $project["project_id"] .
                "'>" .

                htmlspecialchars(
                    $project["project_name"]
                ) .

                "</option>";

            }

        } else {

            // The user cannot make a reservation without being a member of a project

            echo
            "<option value='' disabled>
                No projects available
            </option>";

        }

        ?>

    </select>

    <br><br>

    <!-- =========================
         Reagent lot selection
         ========================= -->

    Reagent lot:

    <select
        name="lot_id"
        required
    >

        <?php

        // Check whether there are reagent lots with available quantity

        if ($lot_result->num_rows > 0) {


            while (
                $lot =
                $lot_result->fetch_assoc()
            ) {


                // The user sees useful reagent information, but the submitted value is only lot_id.

                echo
                "<option value='" .
                $lot["lot_id"] .
                "'>" .

                htmlspecialchars(
                    $lot["name"] .
                    " - " .
                    $lot["catalog_number"] .
                    " - Lot " .
                    $lot["lot_number"] .
                    " - Available: " .
                    $lot["available_quantity"] .
                    " " .
                    $lot["unit"]
                ) .

                "</option>";

            }

        } else {

            echo
            "<option value='' disabled>
                No reagent lots available
            </option>";

        }

        ?>

    </select>

    <br><br>

    <!-- Quantity that the user wants to reserve -->

    Quantity:

    <input
        type="number"
        name="quantity_reserved"
        min="0.01"
        step="0.01"
        required
    >

    <br><br>

    <!-- Date when the reagent is needed -->

    Needed by:

    <input
        type="date"
        name="needed_date"
        required
    >

    <br><br>

    <!-- Submit the reservation -->

    <input
        class="button"
        type="submit"
        value="Reserve"
    >

</form>

</body>

</html>


<?php

// Close the prepared project query
$project_stmt->close();


// Close database connection
$link->close();

?>