<?php

/*
Displays a form for adding an existing user to an existing project.
Users and projects are loaded from the database and shown in dropdown menus.
*/

// Start the session so that we can check
// the currently logged-in user's role
session_start();

// If the user is not logged in,
// redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
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

// Include database connection
include 'db.php';


// Get all projects from the database
// These projects will be shown in the project dropdown

$project_sql =
"SELECT
    project_id,
    project_name

 FROM projects

 ORDER BY project_name";


$project_result =
    $link->query($project_sql);


// Get all users from the database
// These users will be shown in the user dropdown

$user_sql =
"SELECT
    user_id,
    username,
    firstname,
    lastname,
    email

 FROM users

 ORDER BY username";


$user_result =
    $link->query($user_sql);

?>


<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Add Project Member</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>


<?php
// Main header and navigation bar
include "navbar.php";
?>

<main class="page">

<!-- Main heading -->
<h1>Add Project Member</h1>


<!--
    This form links an existing user
    to an existing project.

    The selected project_id and user_id
    will be sent to insert_project_member.php.
-->
<form
    action="insert_project_member.php"
    method="POST"
>


    <!-- Project selection -->
    Project:

    <select
        name="project_id"
        required
    >


        <?php

        // Loop through all projects
        // and create one <option> for each project

        while (
            $project =
            $project_result->fetch_assoc()
        ) {

            echo
            "<option value='" .
            $project["project_id"] .
            "'>" .

            htmlspecialchars(
                $project["project_name"]
            ) .

            "</option>";

        }

        ?>


    </select>


    <br><br>


    <!-- User selection -->
    User:

    <select
        name="user_id"
        required
    >


        <?php

        // Loop through all users
        // and create one <option> for each user

        while (
            $user =
            $user_result->fetch_assoc()
        ) {

            echo
            "<option value='" .
            $user["user_id"] .
            "'>" .

            htmlspecialchars(
                $user["username"] .
                " - " .
                $user["firstname"] .
                " " .
                $user["lastname"] .
                " (" .
                $user["email"] .
                ")"
            ) .

            "</option>";

        }

        ?>


    </select>


    <br><br>


    <!-- Submit the selected project and user -->
    <input
        class="button"
        type="submit"
        value="Add Member"
    >


</form>

</main>

</body>

</html>


<?php

// Close database connection
$link->close();

?>