<?php

include 'db.php';


// Fetch data from POST request

$name =
    $_POST['name'];

$supplier =
    $_POST['supplier'];

$catalog_number =
    $_POST['catalog_number'];

$category =
    $_POST['category'];

$storage_condition =
    $_POST['storage_condition'];

$total_qty = 0;

$available_qty = 0;

// SQL statement

$sql =
"INSERT INTO reagents
(
    name,
    supplier,
    catalog_number,
    category,
    storage_condition,
    total_quantity,
    available_quantity
)

VALUES (?, ?, ?, ?, ?, ?, ?)";


$stmt =
    $link->prepare($sql);


// First five variables are strings, last two are numbers

$stmt->bind_param(
    "sssssdd",
    $name,
    $supplier,
    $catalog_number,
    $category,
    $storage_condition,
    $total_qty,
    $available_qty
);


$result =
    $stmt->execute();


if ($result) {

    echo "New reagent created successfully.";

    echo '<br><br>';

    echo '<a href="add_reagent.php">
          Add another reagent
          </a>';

    echo '<br>';

    echo '<a href="index.php">
          Back to home
          </a>';

} else {

    echo "Error: " .
         $stmt->error;
}


$stmt->close();
$link->close();

?>