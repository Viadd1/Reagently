<?php
include 'db.php';

// TO IMPLEMENT
// all users are part of projects, we want to save only the project id in reservation, not the specific use


// Fetch data from form
// do we also need catalog nr here since lot nr not global?

$lot_id= $_POST['lot_id'];

$volume = $_POST['volume'];

$date = $_POST['date'];

$user_id = 1; // Login function not implemented yet, will be changed later

//trial and error, first draft!
// Check if reagent is in stock

//select only volume
//CHANGED TO TABLE REAGENTS SINCE THAT IS THE TABLE THAT INCLUDES VOLUME
$sql = "SELECT available_quantity, reagent_id   
        FROM reagent_lots
        WHERE lot_id = ?";

$stmt = 
    $link->prepare($sql);

$stmt -> bind_param(
    "di",
    $available_volume,
    $lot_id
);

// execute and get results
$result = $stmt->execute();

$result = $stmt-> get_result();

if ($result-> num_rows > 0) {
    while($row= $result->fetch_assoc()) {
        "<tr> <td>" . $row["mid"] . "</td><td>" . $row["mname"] . "</td><td>" . $row["myear"] . "</td><td>" . $row["mrating"] . "</td><td>" . $row["mgenreid"] . "</td></tr>";
        }
} else {
    0 results;
}

// communicate to user how much is possible to reserve

if ($result) {
    echo "Reagent $lot_id is in stock with $available_volume volume possible to reserve" . "<br>";

} else {
    echo "Reagent is not available for reservation";

}

// Add to order request
// this table not made yet!!
$sql = "INSERT INTO order_requests   
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
    echo "Order request made!";
} else {
    echo "Error: " . $stmt->error;
}

//trial end

// SQL queries

// Update lot quantity

$sql = "UPDATE reagents
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
// maybe update so it also includes catalog id, since lot id is not global for reagents?
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