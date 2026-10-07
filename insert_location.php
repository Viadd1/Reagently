<?php

/*
Adds a new storage location to the database using data submitted from add_location.php.
*/

// Resume existing session
session_start();

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

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

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Add Location</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<?php

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


?>

<body>

<?php
// Main header and navigation bar
include "navbar.php";
?>

<main class="page">

<?php
// Check result

if ($result) {

    echo "New location created successfully.";

    echo '<br><br>';

    echo '<a class="button" href="add_location.php">
          Add another location
          </a>';

} else {

    echo "Error: " .
         $stmt->error;
}

?>

</main>

</body>

</html>

<?php
// Close

$stmt->close();
$link->close();

?>