<?php

/*
Adds a new storage location to the database using data submitted from add_location.php.
*/



include 'db.php';


// Fetch data from POST request

$room =
    $_POST['room'];

$storage_unit =
    $_POST['storage_unit'];

$shelf =
    $_POST['shelf'];

$box_name =
    $_POST['box_name'];


// SQL statement

$sql =
"INSERT INTO locations
(
    room,
    storage_unit,
    shelf,
    box_name
)

VALUES (?, ?, ?, ?)";


// Prepare statement

$stmt =
    $link->prepare($sql);


// Bind variables

$stmt->bind_param(
    "ssss",
    $room,
    $storage_unit,
    $shelf,
    $box_name
);


// Execute

$result =
    $stmt->execute();


// Check result

if ($result) {

    echo "New location created successfully.";

    echo '<br><br>';

    echo '<a href="add_location.php">
          Add another location
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