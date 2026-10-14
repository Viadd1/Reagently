<!-- Request Order 
Allows a logged-in user to submit a text request for a reagent that is out of stock or not currently registered. -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
include BASE_PATH . '/db.php';

// Default values
$reagent_id = null;
$reagent_name = "";

//Existing reagent
// If the request came from an existing reagent that is out of stock: request_order.php?reagent_id=3
if (isset($_GET['reagent_id'])) {

    $reagent_id =
        (int) $_GET['reagent_id'];

    // Get reagent name from database
    $sql =
    "SELECT reagent_id, name FROM reagents 
    WHERE reagent_id = ?
    AND is_deleted = 0";

    $stmt = $link->prepare($sql);

    $stmt->bind_param("i", $reagent_id);

    $stmt->execute();

    $result = $stmt->get_result();

    // If reagent exists, get its name
    if ($result->num_rows === 1) {
        $reagent = $result->fetch_assoc();
        $reagent_name = $reagent['name'];
    }
    $stmt->close();
}

// Reagent not found in database
// If the user searched for a reagent that does not exist: request_order.php?search_query=IL-99
elseif (isset($_GET['search_query'])) {
    $reagent_name = trim($_GET['search_query']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reagently - Request order</title>
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

    <h2>Request Order</h2>

    <p>
        Send a request to the administrator
        if a reagent needs to be ordered.
    </p>

    <br>

    <form
        action="<?= BASE_URL ?>db_connections/insert_order_request.php"
        method="POST"
    >

        <!-- reagent_id is available when the reagent already exists in the database.
            It will be empty if the searched reagent does not exist yet. -->
        <input
            type="hidden"
            name="reagent_id"
            value="<?php
                echo htmlspecialchars(
                    $reagent_id ?? ""
                );
            ?>"
        >

        <!-- Reagent name -->
        Reagent:

        <br>

        <input
            type="text"
            name="reagent_name"
            value="<?php
                echo htmlspecialchars(
                    $reagent_name
                );
            ?>"
            required
        >

        <br><br>

        <!-- User writes the order request -->
        Request:

        <br>

        <textarea
            name="request_text"
            rows="6"
            cols="50"
            placeholder="Please describe what reagent you need and any relevant information."
            required
        ></textarea>

        <br><br>

        <input
            class="button"
            type="submit"
            value="Submit Request"
        >

    </form>

    <a class="button" href="<?= BASE_URL ?>view_reagents.php">
        Back to reagents
    </a>

</main>
</body>
</html>

<?php
$link->close();
?>