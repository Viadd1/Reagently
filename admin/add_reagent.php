<!--
Displays a form for adding a new reagent.
The form sends the reagent information to insert_reagent.php.
-->

<?php
// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in,
// redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /login.php");
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

    <title>Reagently - Add Reagent</title>

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

<h1>Add Reagent</h1>

<form
    action="<?= BASE_URL ?>db_connections/insert_reagent.php"
    method="POST"
>

    Name:
    <input
        type="text"
        name="name"
        required
    >

    <br><br>



    Catalog number:
    <input
        type="text"
        name="catalog_number"
    >

    <br><br>


    Category:
    <input
        type="text"
        name="category"
    >

    <br><br>


    Unit:
    <input
        type="text"
        name="unit"
        placeholder="mL, g, mg..."
        required
    >

    <br><br>


    Storage condition:
    <input
        type="text"
        name="storage_condition"
        placeholder="4°C, -20°C, room temp..."
    >

    <br><br>


    <input
        class="button"
        type="submit"
        value="Add Reagent"
    >

</form>

</main>

</body>

</html>