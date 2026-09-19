<?php

/*
Displays a form for adding an existing user to an existing project.
Users and projects are loaded from the database and shown in dropdown menus.
*/


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

    <!-- Title displayed in the browser tab -->
    <title>Add Project Member - Reagently</title>

</head>


<body>


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
        type="submit"
        value="Add Member"
    >


</form>


<br>


<!-- Return to home page -->
<a href="index.php">
Back to home
</a>


</body>

</html>


<?php

// Close database connection
$link->close();

?>