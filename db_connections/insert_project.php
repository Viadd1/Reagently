<!-- DB Connections - Insert Project 
Creates a new project in the database using data submitted from add_project.php. -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// Include database connection
include BASE_PATH . '/db.php';

// Fetch data submitted from add_project.php
$project_name =
    $_POST['project_name'];
$description =
    $_POST['description'];
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Create Project</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>

<?php

// SQL statement
// project_id is not included because it is AUTO_INCREMENT.
// created_at is not included because MySQL automatically uses the current timestamp.
$sql =
"INSERT INTO projects
(
    project_name,
    description
)

VALUES (?, ?)";

// Prepare SQL statement
// Prepared statements are used so that user input is not directly inserted into the SQL string.
$stmt =
    $link->prepare($sql);

// Bind variables
// project_name = string
// description  = string
// Therefore the type definition is "ss".
$stmt->bind_param(
    "ss",
    $project_name,
    $description
);

// Execute the INSERT statement
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

// Check whether the project was created successfully
if ($result) {

    echo "Project created successfully.";

    echo '<br><br>';

    echo '<a class="button" href=' . BASE_URL . 'admin/add_project.php>
          Create another project
          </a>';

} else {

    // Display database error if the INSERT fails
    echo "Error: " .
         $stmt->error;
}
?>

</main>
</body>
</html>

<?php
// Close prepared statement
$stmt->close();
// Close database connection
$link->close();
?>