<!--
Displays a form for creating a new research project.
The form sends the project information to insert_project.php.
-->
<?php
session_start();
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
        type="submit"
        value="Create Project"
    >


</form>


<br>


<!-- Return to the home page -->
<a href="index.php">
Back to home
</a>


</main>

</body>

</html>