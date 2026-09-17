<?php
include 'db.php';

// Fetch data from form

$lot_id= $_POST['lot_id'];

$volume = $_POST['volume'];

$date = $_POST['date'];


$user_id = 1; // Login function not implemented yet, will be changed later

// SQL queries

// Update lot quantity

$sql = "UPDATE reagent_lots
        SET available_quantity = available_quantity - ?
        WHERE lot_id = ?";

$stmt = 
    $link->prepare($sql);

$stmt -> bind_param(
    "di",
    $volume,
    $lot_id
);

$result = 
    $stmt->execute();

if ($result) {
    echo "Updated quantity 1" . "<br>";

} else {
    echo "Error: " . $stmt->error;

}

// Update reagent quantity

$sql = "SELECT reagent_id 
        FROM reagent_lots 
        WHERE lot_id = ?";

$stmt = 
    $link->prepare($sql);

$stmt -> bind_param(
    "i", 
    $lot_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $reagent_id = $row['reagent_id'];
}

$sql = "UPDATE reagents
        SET available_quantity = available_quantity - ?
        WHERE reagent_id = ?";

$stmt = 
    $link->prepare($sql);

$stmt -> bind_param(
    "di",
    $volume,
    $reagent_id
);

$result = 
    $stmt->execute();

if ($result) {
    echo "Updated quantity 2" . "<br>";

} else {
    echo "Error: " . $stmt->error;

}

// Add reservation to database

$sql = "INSERT INTO reservations
(
    volume,
    date,
    user_id,
    lot_id
    )
    VALUES (?, ?, ?, ?)";

$stmt = 
    $link->prepare($sql);

$stmt -> bind_param(
    "dsii",
    $volume,
    $date,
    $user_id,
    $lot_id
);

$result = 
    $stmt-> execute();

if ($result) {
    echo "Reservation made!";
} else {
    echo "Error: " . $stmt->error;
}

$link->close();
?>