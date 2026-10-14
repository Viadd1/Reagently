<!-- DB Connection - Insert reagent
Adds a new reagent to the database using data submitted from add_reagent.php. -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

include BASE_PATH . '/db.php';

// Fetch data from POST request
$name =
    $_POST['name'];

$catalog_number =
    $_POST['catalog_number'];

$category =
    $_POST['category'];

$unit =
    $_POST['unit'];

$storage_condition =
    $_POST['storage_condition'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reagently - Add Reagent</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL?>style.css"
    >
</head>

<?php

// SQL statement
$sql =
"INSERT INTO reagents
(
    name,
    catalog_number,
    category,
    unit,
    storage_condition
)

VALUES (?, ?, ?, ?, ?)";

$stmt =
    $link->prepare($sql);

// First five variables are strings, last two are numbers
$stmt->bind_param(
    "sssss",
    $name,
    $catalog_number,
    $category,
    $unit,
    $storage_condition
);

?>

<body>

<?php

// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>
<main class="page">
<?php

// Try to insert the reagent
try {

    $result =
        $stmt->execute();

    // Reagent was created successfully
    if ($result) {

        echo "New reagent created successfully.";

        echo "<br><br>";

        echo
        '<a class="button" href=' . BASE_URL . 'admin/add_reagent.php>
            Add another reagent
        </a>';

        echo
        '<a class="button" href=' . BASE_URL . 'admin/add_lot.php>
            Add Reagent Lot
        </a>';
    }

} catch (mysqli_sql_exception $e) {
    // Error code 1062 means a UNIQUE value already exists in the database
    if ($e->getCode() == 1062) {

        echo
            "A reagent with this catalog number already exists.";

        echo "<br><br>";

        echo
            "If you want to add a new batch of the same reagent,
            please add a new reagent lot instead.";

        echo "<br><br>";

        echo
            '<a class="button" href=' . BASE_URL . 'admin/add_lot.php>
                Add Reagent Lot
            </a>';

        echo
            '<a class="button" href=' . BASE_URL . 'admin/add_reagent.php>
                Back to Add Reagent
            </a>';
    } else {

        // Show other database errors
        echo
            "Database error: " .
            $e->getMessage();
    }
}
?>

</main>
</body>
</html>

<?php
$stmt->close();
$link->close();
?>