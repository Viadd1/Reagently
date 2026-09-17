<?php

include 'db.php';


// Fetch POST data

$reagent_id =
    $_POST['reagent_id'];

$location_id =
    $_POST['location_id'];

$lot_number =
    $_POST['lot_number'];

$quantity =
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
    available_quantity,
    unit,
    received_date,
    expiry_date
)

VALUES (?, ?, ?, ?, ?, ?, ?, ?)";


$stmt =
    $link->prepare($sql);


$stmt->bind_param(
    "iisddsss",

    $reagent_id,
    $location_id,
    $lot_number,
    $quantity,
    $quantity,
    $unit,
    $received_date,
    $expiry_date
);


$result =
    $stmt->execute();


if ($result) {

    echo "New reagent lot created successfully.";

} else {

    echo "Error: " .
         $stmt->error;
}


$sql =
"UPDATE reagents
SET total_quantity = total_quantity + ?, available_quantity = available_quantity + ?
WHERE reagent_id = ?";


$stmt =
    $link->prepare($sql);


$stmt->bind_param(
    "ddi",
    $quantity,
    $quantity,
    $reagent_id
);


$result =
    $stmt->execute();


if ($result) {

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