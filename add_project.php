<!--
Displays a form for creating a new research project.
The form sends the project information to insert_project.php.
-->

<?php
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

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Create Project</title>

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
<h1>Create Project</h1>


<!--
    Project creation form

    The entered project information will be sent
    to insert_project.php using the POST method.
-->
<form
    action="insert_project.php"
    method="POST"
>


    <!-- Project name is required because
         project_name is NOT NULL in the database -->
    Project name:

    <input
        type="text"
        name="project_name"
        required
    >

    <br><br>


    <!-- Optional description of the project -->
    Description:

    <br>

    <textarea
        name="description"
        rows="5"
        cols="40"
    ></textarea>

    <br><br>


    <!-- Submit the form -->
    <input
        class="button"
        type="submit"
        value="Create Project"
    >


</form>

</main>

</body>

</html>