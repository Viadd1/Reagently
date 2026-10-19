<!-- DB connection - Edit Project -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: /authentication/login.php");
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

include BASE_PATH . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$project_id = (int)($_POST['project_id'] ?? 0);

$stmt = $link->prepare(
    "SELECT p.project_name, p.description, u.user_id, u.username
     FROM projects p
     JOIN project_members m ON p.project_id = m.project_id
     JOIN users u ON m.user_id = u.user_id
     WHERE p.project_id = ?
     ORDER BY u.username");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) { die("Project not found."); }
$stmt->close();

$stmt = $link->prepare(
"SELECT m.project_id, u.user_id, u.username, u.lab
     FROM users u
     JOIN project_members m ON u.user_id = m.user_id
     WHERE m.project_id = ?
     AND u.username NOT LIKE 'null'
     ORDER BY u.username");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project_members = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Edit Reagent</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>

<?php

// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">
    <h2>Edit Reagent</h2><br>
    <form action="update_reagent.php" method="POST">
        <input type="hidden" name="project" value="<?= $project_id ?>">

        <label>Name:
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($project['project_name']) ?>"></label><br><br>

        <label>Description:
            <textarea
                name="description" rows="5" cols="40"
                ><?= htmlspecialchars($project['description']) ?></textarea><br><br>

        <input class="button" type="submit" value="Save changes">
    </form>
    <a class="button" href="<?= BASE_URL ?>admin/manage_projects.php">Cancel</a>

<br><br><br>

<h2>Manage Project Members</h2>
<table class="table">
        <tr>
            <th>Username</th><th>Lab</th><th>Action</th>
        </tr>
        
        <?php while ($row = $project_members->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['lab']) ?></td>

            <td>
                                
                <form action="<?= BASE_URL ?>db_connections/delete_project_member.php" method="POST"
                      onsubmit="return confirm('Remove this user from the project?');">
                    <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                    <input type="hidden" name="project_id" value="<?= (int)$row['project_id'] ?>">
                    <input class="button" type="submit" value="Remove">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>

    </table>

<a class="button" href="<?= BASE_URL ?>admin/add_project_member.php">Add More Project Members</a>

</main>
</body>
</html>

<?php $stmt->close(); $link->close(); ?>