<?php

include 'db.php';


$room =
    $_POST['room'];

$storage_unit =
    $_POST['storage_unit'];

$shelf =
    $_POST['shelf'];

$box_name =
    $_POST['box_name'];


$sql =
"INSERT INTO locations
(
    room,
    storage_unit,
    shelf,
    box_name
)

VALUES (?, ?, ?, ?)";


$stmt =
    $link->prepare($sql);


$stmt->bind_param(
    "ssss",
    $room,
    $storage_unit,
    $shelf,
    $box_name
);


$result =
    $stmt->execute();


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


$stmt->close();
$link->close();

?>