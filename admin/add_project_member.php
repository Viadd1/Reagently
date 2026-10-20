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
$projectStmt = $link->prepare('SELECT project_id, project_name FROM projects WHERE company_id = ? ORDER BY project_name');
$projectStmt->bind_param('i', $companyId);
$projectStmt->execute();
$project_result = $projectStmt->get_result();
$userStmt = $link->prepare("SELECT user_id, username, firstname, lastname, email
    FROM users WHERE company_id = ? AND role IN ('user','admin') AND approved = 1 AND is_verified = 1 ORDER BY username");
$userStmt->bind_param('i', $companyId);
$userStmt->execute();
$user_result = $userStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Add Project Member</title>
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
<h1>Add Project Member</h1>

<!-- This form links an existing user to an existing project.
The selected project_id and user_id will be sent to insert_project_member.php.-->
<form
    action="<?= BASE_URL ?>db_connections/insert_project_member.php"
    method="POST"
>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <!-- Project selection -->
    Project:
    <select
        name="project_id"
        required
    >
        <?php
        // Loop through all projects and create one <option> for each project
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
        // Loop through all users and create one <option> for each user
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

<?php
// Close database connection
$projectStmt->close();
$userStmt->close();
$link->close();
?>