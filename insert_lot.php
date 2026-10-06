<?php

/*
Creates a new reagent lot and connects it to an existing reagent and storage location.
*/


include 'db.php';


// Fetch data from POST request

$reagent_id =
    $_POST['reagent_id'];

$location_id =
    $_POST['location_id'];

$supplier =
    $_POST['supplier'];

$lot_number =
    $_POST['lot_number'];

$current_quantity =
    $_POST['current_quantity'];

$received_date =
    !empty($_POST['received_date'])
    ? $_POST['received_date']
    : null;

$expiry_date =
    !empty($_POST['expiry_date'])
    ? $_POST['expiry_date']
    : null;


// SQL statement

$sql =
"INSERT INTO reagent_lots
(
    reagent_id,
    location_id,
    supplier,
    lot_number,
    current_quantity,
    received_date,
    expiry_date
)

VALUES (?, ?, ?, ?, ?, ?, ?)";


// Prepare statement

$stmt =
    $link->prepare($sql);


// Bind variables

$stmt->bind_param(
    "iissdss",
    $reagent_id,
    $location_id,
    $supplier,
    $lot_number,
    $current_quantity,
    $received_date,
    $expiry_date
);


// Execute

$result =
    $stmt->execute();


// Check result

if ($result) {

    echo "New reagent lot created successfully.";

    echo '<br><br>';

    echo '<a href="add_lot.php">
          Add another lot
          </a>';

    echo '<br>';

    echo '<a href="index.php">
          Back to home
          </a>';

} else {

    echo "Error: " .
         $stmt->error;
}


// Close

$stmt->close();
$link->close();

?>