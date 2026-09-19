<?php

/*
Adds a user to a project by creating a new record in the project_members table.
*/


// Include database connection
include 'db.php';


// Get the selected project ID from the form

$project_id =
    $_POST['project_id'];


// Get the selected user ID from the form

$user_id =
    $_POST['user_id'];


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


// Check whether the member was added successfully

if ($result) {

    echo "Project member added successfully.";

    echo '<br><br>';

    echo '<a href="add_project_member.php">
          Add another project member
          </a>';

    echo '<br>';

    echo '<a href="index.php">
          Back to home
          </a>';

} else {

    // Display an error message if the INSERT fails
    //
    // One possible reason is that the same user
    // is already a member of the same project.

    echo "Error: " .
         $stmt->error;
}


// Close prepared statement

$stmt->close();


// Close database connection

$link->close();

?>