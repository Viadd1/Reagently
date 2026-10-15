<!-- Displays a form for adding a new reagent storage location.
The form sends the location information to insert_location.php. -->

<?php
// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// Only admins can access this page
if (
    !isset($_SESSION['role'])
    ||
    $_SESSION['role'] !== 'admin'
) {
    die(
        "Access denied. Admin permission is required."
    );
}
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Add Location</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>
<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

<h1>Add Storage Location</h1>

<form
    action="<?= BASE_URL ?>db_connections/insert_location.php"
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
        class="button"
        type="submit"
        value="Add Location"
    >
</form>

<br>

<a
    class="button"
    href="<?= BASE_URL ?>admin.php"
>
    Back to Admin
</a>

</main>
</body>
</html>