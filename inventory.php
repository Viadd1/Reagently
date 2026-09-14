<?php

include 'db.php';


$sql =
"SELECT

    reagent_lots.lot_id,

    reagents.name,

    reagents.supplier,

    reagents.catalog_number,

    reagents.category,

    reagent_lots.lot_number,

    reagent_lots.current_quantity,

    reagent_lots.unit,

    locations.room,

    locations.storage_unit,

    locations.shelf,

    locations.box_name,

    reagent_lots.expiry_date

FROM reagent_lots


JOIN reagents

ON reagent_lots.reagent_id
=
reagents.reagent_id


JOIN locations

ON reagent_lots.location_id
=
locations.location_id


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

    <th>Supplier</th>

    <th>Catalog No.</th>

    <th>Category</th>

    <th>Lot</th>

    <th>Quantity</th>

    <th>Location</th>

    <th>Expiry Date</th>

</tr>


<?php


if ($result->num_rows > 0) {


    while (
        $row =
        $result->fetch_assoc()
    ) {


        echo "<tr>";


        echo "<td>" .
        htmlspecialchars(
            $row["name"]
        ) .
        "</td>";


        echo "<td>" .
        htmlspecialchars(
            $row["supplier"]
        ) .
        "</td>";


        echo "<td>" .
        htmlspecialchars(
            $row["catalog_number"]
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


        echo "<td>" .
        htmlspecialchars(
            $row["current_quantity"]
        ) .
        " " .
        htmlspecialchars(
            $row["unit"]
        ) .
        "</td>";


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
        <td colspan='8'>
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