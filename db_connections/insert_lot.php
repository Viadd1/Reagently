<!-- DB connection - Insert Lot
Creates a new reagent lot and connects it to an existing reagent and storage location. -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
include BASE_PATH . '/db.php';

// Fetch data from POST request
$reagent_id =
    $_POST['reagent_id'];

$location_id =
    $_POST['location_id'];

$supplier =
    $_POST['supplier'];

$lot_number =
    $_POST['lot_number'];

$current_quantity =
    $_POST['current_quantity'];

$received_date =
    !empty($_POST['received_date'])
    ? $_POST['received_date']
    : null;

$expiry_date =
    !empty($_POST['expiry_date'])
    ? $_POST['expiry_date']
    : null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Reagently - Add Reagent Lot</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>

<?php

// SQL statement
$sql =
"INSERT INTO reagent_lots
(
    reagent_id,
    location_id,
    supplier,
    lot_number,
    current_quantity,
    received_date,
    expiry_date
)

VALUES (?, ?, ?, ?, ?, ?, ?)";

// Prepare statement
$stmt =
    $link->prepare($sql);

// Bind variables
$stmt->bind_param(
    "iissdss",
    $reagent_id,
    $location_id,
    $supplier,
    $lot_number,
    $current_quantity,
    $received_date,
    $expiry_date
);

// Execute
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

// Check result
if ($result) {

    echo "New reagent lot created successfully.";

    echo '<br><br>';

    echo '<a class="button" href=' . BASE_URL . 'admin/add_lot.php>
          Add another lot
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