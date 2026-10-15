<!-- View Reagents 
Displays the current reagent inventory, including physical, reserved and available quantities for each reagent lot. -->

<?php

// Start session and set base path variable
require_once "init.php";

// If the user is not logged in then redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// Include database connection
include 'db.php';

// Give default values when the page is opened before any search or filter has been submitted
$_GET['search_query'] =
    $_GET['search_query'] ?? '';

$_GET['stock_filter'] =
    $_GET['stock_filter'] ?? 'show_all';

// If user has searched for something, save in variable
if ($_GET['search_query']) {
    $search_query = $_GET['search_query'];
} 
else {
    $search_query = "";
}

// Check if the user has filled in a stock filter and alter the WHERE condition accordingly
if ($_GET['stock_filter']==='in_stock') {
    $stock_filter = '`reagent_lots`.`current_quantity` > 0';
}
elseif ($_GET['stock_filter']==='not_in_stock') {
    $stock_filter = '`reagent_lots`.`current_quantity` = 0';
}
else {
    $stock_filter = '`reagent_lots`.`current_quantity` >= 0';
}

$_GET['category_filter'] =
    $_GET['category_filter'] ?? '';

// Get reagent inventory
// Retrieve reagent lot information.
// Physical quantity = reagent_lots.current_quantity
// Reserved quantity = total quantity from ALL ACTIVE reservations
// Available quantity = Physical quantity - Reserved quantity
// The reserved quantity is calculated using ALL active reservations from ALL projects.
// Users do not need to see reservation details from other projects, but those reservations still affect the available stock.
$sql =
"SELECT

    reagent_lots.lot_id,
    reagents.reagent_id,

    reagents.name,
    (
    SELECT GROUP_CONCAT(
        rn.nickname
        ORDER BY rn.nickname
        SEPARATOR ', '
    )
    FROM reagent_nicknames rn
    WHERE rn.reagent_id = reagents.reagent_id
) AS nicknames,
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
JOIN reagents ON reagent_lots.reagent_id = reagents.reagent_id

-- Connect each lot to its storage location
JOIN locations ON reagent_lots.location_id = locations.location_id

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

ON reagent_lots.lot_id = reservation_totals.lot_id

-- Only show reagents that match search query and filters
WHERE reagents.is_deleted = 0

AND (
    reagents.name LIKE ?
    OR EXISTS (
        SELECT 1 FROM reagent_nicknames
        WHERE reagent_nicknames.reagent_id = reagents.reagent_id
        AND reagent_nicknames.nickname LIKE ?
    )
)

AND $stock_filter

AND (reagents.category = ?
    OR ? = '')

-- Sort reagents alphabetically
ORDER BY
    reagents.name,
    reagent_lots.expiry_date";

// Execute the SQL query
$like = '%' . $search_query . '%';
$category_filter = $_GET['category_filter'] ?? '';
$stmt = $link->prepare($sql);
$stmt->bind_param("ssss", $like, $like, $category_filter, $category_filter);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - View Reagents</title>
    <!-- Use existing website stylesheet -->
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

<!-- Main page -->
<main class="page">

    <h2>View Reagents</h2>
    <p>
        Current reagent inventory and availability.
    </p>
    <br>

<!-- Search bar and filters -->
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
            class="button"
            type="submit"
            value="Search">

            <br><br>

        Category:
        <select name="category_filter" id="category_filter">
            <option value="">All categories</option>

            <option value="organic"
                <?= (($_GET['category_filter'] ?? '') === 'organic')
                    ? 'selected' : '' ?>>
                Organic
            </option>

            <option value="inorganic"
                <?= (($_GET['category_filter'] ?? '') === 'inorganic')
                    ? 'selected' : '' ?>>
                Inorganic
            </option>

            <option value="biochemical"
                <?= (($_GET['category_filter'] ?? '') === 'biochemical')
                    ? 'selected' : '' ?>>
                Biochemical
            </option>

            <option value="other"
                <?= (($_GET['category_filter'] ?? '') === 'other')
                    ? 'selected' : '' ?>>
                Other
            </option>
        </select>

            <input 
            type="submit" 
            class="button" 
            value="Filter">

            <br><br>

            <input
            type="radio"
            id="all"
            name="stock_filter"
            value="show_all"
            <?= ($_GET['stock_filter'] ?? 'show_all') === 'show_all'
                ? 'checked' : '' ?>
            <label
            for="all">Show all  
            </label>

            <input
            type="radio"
            id="in_stock"
            name="stock_filter"
            value="in_stock"
            <?= ($_GET['stock_filter'] ?? 'show_all') === 'in_stock'
                ? 'checked' : '' ?>
            >
            <label
            for="in_stock">In stock  
            </label>

            <input
            type="radio"
            id="not_in_stock"
            name="stock_filter"
            value="not_in_stock"
            <?= ($_GET['stock_filter'] ?? 'show_all') === 'not_in_stock'
                ? 'checked' : '' ?>
            >
            <label
            for="not_in_stock">Not in stock  
            </label>

            <br><br>

        </form>
    </search>

    <!-- Inventory table -->
    <table class="table">

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

            <th>Action</th>

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
                );

                // Nickname
                if (!empty($row["nicknames"])) {
                    echo " <span class='reagent-nicknames'>(" .
                        htmlspecialchars($row["nicknames"]) .
                        ")</span>";
                }

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
                // Available quantity is calculated: Available = Physical - Reserved
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
                // Build the full storage location: Room / Storage unit / Shelf / Box
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

                // Action button
                echo "<td>";

                if ($row["available_quantity"] > 0) {
                    echo
                    "<a
                         class='button'
                         href=" . BASE_URL . "user/make_reservation.php?lot_id=" .
                         $row["lot_id"] .
                         "'>
                         Reserve
                    </a>";
                }
                else {
                    echo
                    "<a
                        class='button'
                        href=" . BASE_URL . "user/request_order.php?reagent_id=" .
                        $row["reagent_id"] .
                        "'>
                        Request Order
                    </a>";
                }

                echo "</td>";

                echo "</tr>";

            }

        } else {

            // Display this if there are no reagent lots in the database and request order
            echo
            "<tr>
                <td colspan='12'>
                    No reagents found.";
            // if a reagent was searched but does not exist, user can request it
            if (!empty($search_query)) {
                
                echo
                "<br><br>

                <a
                    class='button'
                    href=" . BASE_URL . "user/request_order.php?search_query=" .
                    urlencode($search_query) .
                    "'>
                    Request Order
                </a>";
            }
            echo

                "</td>

            </tr>";
        }
        ?>

    </table>

    <br><br>

    <h4>Need more reagents for your project? Reserve them below!</h4>
    <!-- Go to reservation page -->
    <a
        class="button"
        href="<?= BASE_URL ?>user/make_reservation.php"
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