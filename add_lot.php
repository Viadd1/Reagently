<?php

/*
Displays a form for adding a new lot of an existing reagent.
Existing reagents and storage locations are loaded from the database and shown in dropdown menus.
*/

// Start the session so that we can check
// the currently logged-in user's role
session_start();

// If the user is not logged in,
// redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

// Only admins can access this page
if (
    !isset($_SESSION['role'])
    ||
    $_SESSION['role'] !== 'admin'
) {

    die(
        "Access denied. Admin permission is required."
    );
}

include 'db.php';

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Add Reagent Lot</title>

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

<main class="page">

<h1>Add Reagent Lot</h1>


<form
    action="insert_lot.php"
    method="POST"
>


    Reagent:

    <select
        name="reagent_id"
        required
    >

        <?php

        $sql =
        "SELECT
            reagent_id,
            name,
            catalog_number,
            unit

         FROM reagents

         WHERE is_deleted = 0

         ORDER BY name";


        $result =
            $link->query($sql);


        while (
            $row =
            $result->fetch_assoc()
        ) {

            echo
            "<option value='" .
            $row["reagent_id"] .
            "'>" .

            htmlspecialchars(
                $row["name"] .
                " - " .
                $row["catalog_number"] .
                " (" .
                $row["unit"] .
                ")"
            ) .

            "</option>";

        }

        ?>

    </select>


    <br><br>


    Supplier:

    <input
        type="text"
        name="supplier"
    >


    <br><br>


    Lot number:

    <input
        type="text"
        name="lot_number"
    >


    <br><br>


    Current quantity:

    <input
        type="number"
        step="0.01"
        min="0"
        name="current_quantity"
        required
    >


    <br><br>


    Location:

    <select
        name="location_id"
        required
    >

        <?php

        $sql =
        "SELECT
            location_id,
            room,
            storage_unit,
            shelf,
            box_name

         FROM locations

         ORDER BY room";


        $result =
            $link->query($sql);


        while (
            $row =
            $result->fetch_assoc()
        ) {

            echo
            "<option value='" .
            $row["location_id"] .
            "'>" .

            htmlspecialchars(
                $row["room"] .
                " / " .
                $row["storage_unit"] .
                " / " .
                $row["shelf"] .
                " / " .
                $row["box_name"]
            ) .

            "</option>";
        }

        ?>

    </select>


    <br><br>


    Received date:

    <input
        type="date"
        name="received_date"
    >


    <br><br>


    Expiry date:

    <input
        type="date"
        name="expiry_date"
    >


    <br><br>


    <input
        type="submit"
        value="Add Lot"
    >


</form>


<br>

<a href="index.php">
Back to home
</a>


</main>

</body>

</html>

<?php

$link->close();

?>