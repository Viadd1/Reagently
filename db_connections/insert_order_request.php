<!-- DB Connection - Insert Order Request 
Saves a reagent order request submitted by a logged-in user. -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// User must be logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// Include database connection
include BASE_PATH . '/db.php';

// Get current user's ID from session
$user_id = $_SESSION['user_id'];

// Get submitted form data
$reagent_id = $_POST['reagent_id'] ?? '';

$reagent_name = trim($_POST['reagent_name'] ?? '');

$request_text = trim($_POST['request_text'] ?? '');

?>
<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Request Order</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>

<?php

// Validate form data
// Reagent name cannot be empty
if ($reagent_name === '') {
    die("Reagent name is required.");
}

// Request message cannot be empty
if ($request_text === '') {
    die("Request message is required.");
}

//Insert order request
// There are two possible cases: 
// 1. Existing reagent: reagent_id has a value 
// 2. Reagent does not exist: reagent_id is empty / NULL
if ($reagent_id !== '') {
    // Existing reagent
    $reagent_id = (int) $reagent_id;

    $sql =
    "INSERT INTO order_requests
    (
        user_id,
        reagent_id,
        reagent_name,
        request_text
    )

    VALUES (?, ?, ?, ?)";

    $stmt =
        $link->prepare($sql);

    // user_id      = integer
    // reagent_id   = integer
    // reagent_name = string
    // request_text = string

    $stmt->bind_param(
        "iiss",
        $user_id,
        $reagent_id,
        $reagent_name,
        $request_text
    );

} else {

    // Reagent does not exist in the database.
    // reagent_id is stored as NULL.
    $sql =
    "INSERT INTO order_requests
    (
        user_id,
        reagent_id,
        reagent_name,
        request_text
    )

    VALUES (?, NULL, ?, ?)";

    $stmt =
        $link->prepare($sql);

    // user_id      = integer
    // reagent_name = string
    // request_text = string

    $stmt->bind_param(
        "iss",
        $user_id,
        $reagent_name,
        $request_text
    );
}

// Execute INSERT
$result =
    $stmt->execute();

?>

<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

<?php

//Result
if ($result) {
    echo
        "Order request submitted successfully.";

    echo
        "<br><br>";

    echo
        "Requested reagent: " .
        htmlspecialchars(
            $reagent_name
        );

} else {

    echo
        "Error: " .
        $stmt->error;

}

?>

</main>
</body>
</html>

<?php
// Close statement
$stmt->close();
// Close database connection
$link->close();
?>