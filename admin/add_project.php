<?php
// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_csrf_token();
$link->close();
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
<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

<!-- Main heading -->
<h1>Create Project</h1>

<!-- Project creation form
The entered project information will be sent to insert_project.php using the POST method. -->
<form
    action="<?= BASE_URL ?>db_connections/insert_project.php"
    method="POST"
>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <!-- Project name is required because project_name is NOT NULL in the database -->
    Project name:
    <input
        type="text"
        name="project_name"
        required
    >

    <br><br>

    <!-- Optional description of the project -->
    Description:
    <br><br>
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

<br>

<a
    class="button"
    href="<?= BASE_URL ?>admin.php"
>
    Back to Admin
</a>

</main>
</body>
</html>