<!--
Displays a form for adding a new reagent storage location.
The form sends the location information to insert_location.php.
-->

<?php
session_start();
?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Add Storage Location</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>


<?php
// Main header and navigation bar
include "navbar.php";
?>

<main class="page">

<h1>Add Storage Location</h1>


<form
    action="insert_location.php"
    method="POST"
>

    Room:
    <input
        type="text"
        name="room"
        required
    >

    <br><br>


    Storage unit:
    <input
        type="text"
        name="storage_unit"
    >

    <br><br>


    Shelf:
    <input
        type="text"
        name="shelf"
    >

    <br><br>


    Box:
    <input
        type="text"
        name="box_name"
    >

    <br><br>


    <input
        type="submit"
        value="Add Location"
    >

</form>


<br>

<a href="index.php">
Back to home
</a>


</main>

</body>

</html>