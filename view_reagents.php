<?php

// Displays the current reagent inventory, including physical, reserved and available quantities for each reagent lot.


// Start the session so that we can check whether the user is logged in
session_start();

// If the user is not logged in then redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

// Include database connection
include 'db.php';


// If user has searched for something, save in variable
if ($_GET['search_query']) {
    $search_query = $_GET['search_query'];
} 
else {
    $search_query = "";
}

/* 
Check if the user has filled in a stock filter
and alter the WHERE condition accordingly
*/

if ($_GET['stock_filter']==='in_stock') {
    $stock_filter = '`reagent_lots`.`current_quantity` > 0';
}
elseif ($_GET['stock_filter']==='not_in_stock') {
    $stock_filter = '`reagent_lots`.`current_quantity` = 0';
}
else {
    $stock_filter = '`reagent_lots`.`current_quantity` >= 0';
}

// Get reagent inventory

// Retrieve reagent lot information.

// Physical quantity = reagent_lots.current_quantity

// Reserved quantity = total quantity from ALL ACTIVE reservations

// Available quantity = Physical quantity - Reserved quantity

// IMPORTANT:
// The reserved quantity is calculated using ALL active reservations from ALL projects.
// Users do not need to see reservation details from other projects, but those reservations
// still affect the available stock.

$sql =
"SELECT

    reagent_lots.lot_id,

    reagents.name,
    reagents.catalog_number,
    reagents.category,
    reagents.unit,
    reagents.storage_condition,

    reagent_lots.supplier,
    reagent_lots.lot_number,
    reagent_lots.current_quantity,
    reagent_lots.expiry_date,

    locations.room,
    locations.storage_unit,
    locations.shelf,
    locations.box_name,

    COALESCE(
        reservation_totals.reserved_quantity,
        0
    ) AS reserved_quantity,

    reagent_lots.current_quantity
    -
    COALESCE(
        reservation_totals.reserved_quantity,
        0
    ) AS available_quantity


FROM reagent_lots

-- Connect each lot to its reagent
JOIN reagents

ON reagent_lots.reagent_id
=
reagents.reagent_id

-- Connect each lot to its storage location
JOIN locations

ON reagent_lots.location_id
=
locations.location_id

-- Calculate the total ACTIVE reserved quantity for each reagent lot
LEFT JOIN
(
    SELECT

        lot_id,

        SUM(quantity_reserved)
        AS reserved_quantity

    FROM reservations

    WHERE status = 'ACTIVE'

    GROUP BY lot_id

) AS reservation_totals

ON reagent_lots.lot_id
=
reservation_totals.lot_id

-- Only show reagents that match search query (if there is one)
WHERE reagents.name LIKE '%$search_query%'

AND $stock_filter

-- Sort reagents alphabetically
ORDER BY

    reagents.name,
    reagent_lots.expiry_date";

// Execute the SQL query
$result =
    $link->query($sql);

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        Reagently - View Reagents
    </title>

    <!-- Use your existing website stylesheet -->
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

<!-- Main page -->

<main class="page">

    <h2>View Reagents</h2>

    <p>
        Current reagent inventory and availability.
    </p>
    
    <br>

    <search>
        <form 
        action="/view_reagents.php"
        method="GET"
        >

            <input
            type="text"
            name="search_query"
            placeholder="Search reagents...">

            <input
            type="submit"
            value="Submit">

            <br>

            <input
            type="radio"
            id="all"
            name="stock_filter"
            value="show_all"
            checked>
            <label for="all">Show all</label>

            <input
            type="radio"
            id="in_stock"
            name="stock_filter" value="in_stock"
            >
            <label for="in_stock">In stock</label>

            <input
            type="radio"
            id="not_in_stock"
            name="stock_filter" value="not_in_stock"
            >
            <label for="not_in_stock">Not in stock</label>

        </form>
    </search>


    <!-- Inventory table -->
    <table border="1">

        <tr>

            <th>Reagent</th>

            <th>Catalog No.</th>

            <th>Category</th>

            <th>Supplier</th>

            <th>Lot</th>

            <th>Physical</th>

            <th>Reserved</th>

            <th>Available</th>

            <th>Storage Condition</th>

            <th>Location</th>

            <th>Expiry Date</th>

        </tr>

        <?php

        // Check whether any reagent lots exist
        if ($result->num_rows > 0) {

            // Display one row for each reagent lot
            while (
                $row =
                $result->fetch_assoc()
            ) {

                echo "<tr>";

                // Reagent name
                echo "<td>" .

                htmlspecialchars(
                    $row["name"]
                ) .

                "</td>";

                // Catalog number
                echo "<td>" .

                htmlspecialchars(
                    $row["catalog_number"]
                ) .

                "</td>";

                // Reagent category
                echo "<td>" .

                htmlspecialchars(
                    $row["category"] ?? ""
                ) .

                "</td>";

                // Supplier belongs to the specific lot
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

                /* Physical quantity */

                // Physical quantity is the quantity actually present in the laboratory.

                echo "<td>" .

                htmlspecialchars(
                    $row["current_quantity"]
                ) .

                " " .

                htmlspecialchars(
                    $row["unit"]
                ) .

                "</td>";

                /* Reserved quantity */

                // Reserved quantity is the sum of all ACTIVE reservations for this lot.

                echo "<td>" .

                htmlspecialchars(
                    $row["reserved_quantity"]
                ) .

                " " .

                htmlspecialchars(
                    $row["unit"]
                ) .

                "</td>";

                /* Available quantity */

                // Available quantity is calculated dynamically:
                // Available = Physical - Reserved

                echo "<td>" .

                htmlspecialchars(
                    $row["available_quantity"]
                ) .

                " " .

                htmlspecialchars(
                    $row["unit"]
                ) .

                "</td>";

                // Recommended storage condition for this reagent
                echo "<td>" .

                htmlspecialchars(
                    $row["storage_condition"] ?? ""
                ) .

                "</td>";

                /* Physical location */

                // Build the full storage location:
                // Room / Storage unit / Shelf / Box

                echo "<td>" .

                htmlspecialchars(

                    $row["room"] .

                    " / " .

                    ($row["storage_unit"] ?? "") .

                    " / " .

                    ($row["shelf"] ?? "") .

                    " / " .

                    ($row["box_name"] ?? "")

                ) .

                "</td>";

                // Expiry date
                echo "<td>" .

                htmlspecialchars(
                    $row["expiry_date"] ?? ""
                ) .

                "</td>";

                echo "</tr>";

            }

        } else {

            // Display this if there are no reagent lots in the database yet.

            echo
            "<tr>

                <td colspan='11'>
                    No reagents found.
                </td>

            </tr>";

        }

        ?>

    </table>

    <br><br>

    <h4>Need more reagents for your project? Reserve them below!</h4>
    <!-- Go to reservation page -->
    <a
        class="button"
        href="make_reservation.php"
    >
        Make Reservation
    </a>

</main>

</body>

</html>

<?php

// Close database connection
$link->close();

?>
