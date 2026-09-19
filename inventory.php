<?php

include 'db.php';


// SQL query

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
        SUM(
            CASE
                WHEN reservations.status = 'ACTIVE'
                THEN reservations.quantity_reserved
                ELSE 0
            END
        ),
        0
    ) AS reserved_quantity

FROM reagent_lots


JOIN reagents 

ON reagent_lots.reagent_id
=
reagents.reagent_id


JOIN locations

ON reagent_lots.location_id
=
locations.location_id


LEFT JOIN reservations

ON reagent_lots.lot_id
=
reservations.lot_id


GROUP BY

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
    locations.box_name


ORDER BY reagents.name";


$result =
    $link->query($sql);

?>


<!DOCTYPE html>

<html>

<head>
    <title>Reagent Inventory</title>
</head>

<body>


<h1>Reagent Inventory</h1>


<table border="1">

<tr>

    <th>Reagent</th>

    <th>Catalog No.</th>

    <th>Supplier</th>

    <th>Category</th>

    <th>Lot</th>

    <th>Physical</th>

    <th>Reserved</th>

    <th>Available</th>

    <th>Location</th>

    <th>Expiry Date</th>

</tr>


<?php

// Check whether the query returned any reagent lots
if ($result->num_rows > 0) {

    // Loop through each row returned from the database
    while (
        $row =
        $result->fetch_assoc()
    ) {


        // Calculate available quantity
        //
        // Physical quantity = current_quantity
        // Reserved quantity = sum of ACTIVE reservations
        // Available quantity = Physical quantity - Reserved quantity

        $available_quantity =
            $row["current_quantity"]
            -
            $row["reserved_quantity"];


        echo "<tr>";


        echo "<td>" .
        htmlspecialchars(
            $row["name"]
        ) .
        "</td>";


        echo "<td>" .
        htmlspecialchars(
            $row["catalog_number"]
        ) .
        "</td>";


        echo "<td>" .
        htmlspecialchars(
            $row["supplier"]
        ) .
        "</td>";


        echo "<td>" .
        htmlspecialchars(
            $row["category"]
        ) .
        "</td>";


        echo "<td>" .
        htmlspecialchars(
            $row["lot_number"]
        ) .
        "</td>";


        // Physical quantity

        echo "<td>" .
        htmlspecialchars(
            $row["current_quantity"]
        ) .
        " " .
        htmlspecialchars(
            $row["unit"]
        ) .
        "</td>";


        // Reserved quantity

        echo "<td>" .
        htmlspecialchars(
            $row["reserved_quantity"]
        ) .
        " " .
        htmlspecialchars(
            $row["unit"]
        ) .
        "</td>";


        // Available quantity

        echo "<td>" .
        htmlspecialchars(
            $available_quantity
        ) .
        " " .
        htmlspecialchars(
            $row["unit"]
        ) .
        "</td>";


        // Location

        echo "<td>" .

        htmlspecialchars(
            $row["room"] .
            " / " .
            $row["storage_unit"] .
            " / " .
            $row["shelf"] .
            " / " .
            $row["box_name"]
        ) .

        "</td>";


        echo "<td>" .
        htmlspecialchars(
            $row["expiry_date"]
        ) .
        "</td>";


        echo "</tr>";

    }


} else {


    echo
    "<tr>
        <td colspan='10'>
            No reagents found.
        </td>
    </tr>";

}


?>


</table>


<br>


<a href="index.php">
Back to home
</a>


</body>

</html>


<?php

$link->close();

?>