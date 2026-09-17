<!DOCTYPE html>

<html>

<head>
    <title>Make a Reservation</title>
</head>

<body>

<h1>Request your reagents here</h1>


<form
    action="insert_reservation.php"
    method="POST"
>
    Reagent lot:
    <select
        name="lot_id"
        required
    >

        <?php

        include 'db.php';

        $sql =
        "SELECT
            reagent_lots.lot_id,
            reagents.reagent_id,
            reagents.name,
            reagent_lots.lot_number,
            reagent_lots.available_quantity,
            reagent_lots.unit

         FROM reagent_lots

         JOIN reagents ON 
         reagent_lots.reagent_id=reagents.reagent_id
         
         ORDER BY reagents.name";


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
                $row["available_quantity"] .
                "  " .
                $row["unit"] .
                " in stock"
            ) .

            "</option>";
        }
        $link->close();
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