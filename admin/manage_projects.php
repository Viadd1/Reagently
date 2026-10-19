<!-- Manage Projects -->

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

// Projects
$projects = $link->query(
    "SELECT project_id, project_name, description
     FROM projects
     WHERE project_name NOT LIKE 'null'
     ORDER BY project_name"
);

?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Manage projects</title>
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
    <h2>Manage Projects</h2>

    <?php if (isset($_GET['msg'])): ?>
        <p><strong><?= htmlspecialchars($_GET['msg']) ?></strong></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;"><strong><?= htmlspecialchars($_GET['error']) ?></strong></p>
    <?php endif; ?>

    <table class="table">
        <tr>
            <th>Project</th><th>Description</th><th>Members</th><th>Actions</th>
        </tr>
        <?php while ($row = $projects->fetch_assoc()): 
                    $project_id = $row["project_id"];
                    
                    $sql_members = $link->prepare(
                        "SELECT m.project_id, m.user_id, u.username
                        FROM project_members m
                        JOIN users u ON m.user_id = u.user_id
                        WHERE m.project_id = ?
                        AND u.username NOT LIKE 'null'
                        ORDER BY u.username"
                    );
                    $sql_members->bind_param("i", $project_id);
                    $sql_members->execute();
                    $members = $sql_members->get_result();
?>
        <tr>
            <td><?= htmlspecialchars($row['project_name']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?php while ($member = $members->fetch_assoc()) {
                        echo htmlspecialchars($member['username']) . "<br>";
                    }?></td>

            <td>
                <form action="<?= BASE_URL ?>db_connections/edit_project.php" method="POST">
                    <input type="hidden" name="project_id" value="<?= (int)$row['project_id'] ?>">
                    <input class="button" type="submit" value="Edit">
                </form>

                <br>
                
                <form action="<?= BASE_URL ?>db_connections/delete_project.php" method="POST"
                      onsubmit="return confirm('Delete this project?');">
                    <input type="hidden" name="project_id" value="<?= (int)$row['project_id'] ?>">
                    <input class="button" type="submit" value="Delete">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>


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
$link->close(); 
?>