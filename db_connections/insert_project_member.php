<?php

/*
Adds a user to a project by creating a new record in the project_members table.
*/

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// Include database connection
include BASE_PATH . '/db.php';


// Get the selected project ID from the form

$project_id =
    $_POST['project_id'];


// Get the selected user ID from the form

$user_id =
    $_POST['user_id'];

?>
<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Add Project Member</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >

</head>

<?php

// SQL statement
//
// This inserts one relationship between
// a project and a user into project_members.

$sql =
"INSERT INTO project_members
(
    project_id,
    user_id
)

VALUES (?, ?)";


// Prepare SQL statement

$stmt =
    $link->prepare($sql);


// Bind variables
//
// project_id = integer
// user_id    = integer
//
// Therefore the type definition is "ii".

$stmt->bind_param(
    "ii",
    $project_id,
    $user_id
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

// Check whether the member was added successfully

if ($result) {

    echo "Project member added successfully.";

    echo '<br><br>';

    echo '<a class="button" href=' . BASE_URL . 'admin/add_project_member.php>
          Add another project member
          </a>';

} else {

    // Display an error message if the INSERT fails
    //
    // One possible reason is that the same user
    // is already a member of the same project.

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