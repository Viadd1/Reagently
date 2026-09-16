<?php

include 'db.php';

?>

<!DOCTYPE html>

<html>

<head>
    <title>Make a Reservation</title>
</head>

<body>

<h1>Request your reagents here</h1>


<form
    action="add_reservation.php"
    method="POST"
>

    Reagent lot:
    <select
        name="location_id"
        required
    >

        <?php

        $sql =
        "SELECT
            reagents.name,
            reagent_lots.lot_number,
            reagent_lots.current_quantity,
            reagent_lots.unit

         FROM reagent_lots

         JOIN reagents ON reagent_lots.reagent_id=reagents.reagent_id";


        $result =
            $link->query($sql);


        while (
            $row =
            $result->fetch_assoc()
        ) {

            echo
            "<option value='" .
            $row["lot_id"] .
            "'>" .

            htmlspecialchars(
                $row["name"] .
                " lot no. " .
                $row["lot_number"] .
                ",  " .
                $row["current_quantity"] .
                "  " .
                $row["unit"] .
                " in stock"
            ) .

            "</option>";
        }

        ?>

    </select>
    <br><br>


    Requested volume:
    <input
        type="text"
        name="volume"
    >

    <br><br>


    Requested date:
    <input
        type="date"
        name="date"
    >

    <br><br>


    <input
        type="submit"
        value="Send reservation request"
    >

</form>


<br>

<a href="index.php">
Back to home
</a>

</body>

</html>