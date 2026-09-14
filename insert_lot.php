<?php

include 'db.php';


// Fetch POST data

$reagent_id =
    $_POST['reagent_id'];

$location_id =
    $_POST['location_id'];

$lot_number =
    $_POST['lot_number'];

$current_quantity =
    $_POST['current_quantity'];

$unit =
    $_POST['unit'];

$received_date =
    $_POST['received_date'];

$expiry_date =
    $_POST['expiry_date'];


// SQL

$sql =
"INSERT INTO reagent_lots
(
    reagent_id,
    location_id,
    lot_number,
    current_quantity,
    unit,
    received_date,
    expiry_date
)

VALUES (?, ?, ?, ?, ?, ?, ?)";


$stmt =
    $link->prepare($sql);


$stmt->bind_param(
    "iisdsss",

    $reagent_id,
    $location_id,
    $lot_number,
    $current_quantity,
    $unit,
    $received_date,
    $expiry_date
);


$result =
    $stmt->execute();


if ($result) {

    echo "New reagent lot created successfully.";

    echo '<br><br>';

    echo '<a href="add_lot.php">
          Add another lot
          </a>';

    echo '<br>';

    echo '<a href="inventory.php">
          View inventory
          </a>';

} else {

    echo "Error: " .
         $stmt->error;
}


$stmt->close();
$link->close();

?>