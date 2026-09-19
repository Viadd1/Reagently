<?php

/*
Creates a new project in the database using data submitted from add_project.php.
*/


// Include database connection
include 'db.php';


// Fetch data submitted from add_project.php

$project_name =
    $_POST['project_name'];

$description =
    $_POST['description'];


// SQL statement
//
// project_id is not included because it is AUTO_INCREMENT.
//
// created_at is not included because MySQL automatically
// uses the current timestamp.

$sql =
"INSERT INTO projects
(
    project_name,
    description
)

VALUES (?, ?)";


// Prepare SQL statement
//
// Prepared statements are used so that user input
// is not directly inserted into the SQL string.

$stmt =
    $link->prepare($sql);


// Bind variables
//
// project_name = string
// description  = string
//
// Therefore the type definition is "ss".

$stmt->bind_param(
    "ss",
    $project_name,
    $description
);


// Execute the INSERT statement

$result =
    $stmt->execute();


// Check whether the project was created successfully

if ($result) {

    echo "Project created successfully.";

    echo '<br><br>';

    echo '<a href="add_project.php">
          Create another project
          </a>';

    echo '<br>';

    echo '<a href="index.php">
          Back to home
          </a>';

} else {

    // Display database error if the INSERT fails
    echo "Error: " .
         $stmt->error;
}


// Close prepared statement
$stmt->close();


// Close database connection
$link->close();

?>