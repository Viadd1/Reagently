<?php
/*
Lists all users who are under the supervision of the logged-in admin.
Each user is linked to an admin through users.supervisor_id.
An admin only ever sees their own users.
*/

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Include database connection
include BASE_PATH . "/db.php";

// Only logged-in admins may open this page
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Access denied. Admin permission is required.");
}

// The admin id comes from the session, NOT from the URL or a form,
// so an admin cannot look at another admin's users by changing a value.
$admin_id = (int)$_SESSION['user_id'];

$sql =
"SELECT
    user_id,
    username,
    firstname,
    lastname,
    lab,
    email,
    role,
    approved,
    created_at

 FROM users

 WHERE supervisor_id = ?

 ORDER BY approved ASC, lastname, firstname";

$stmt = $link->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reagently - My Users</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<?php include BASE_PATH . "/navbar.php"; ?>

<main class="page">
    <h2>Users under my supervision</h2>

    <p>
        <?= (int)$result->num_rows ?> user(s). Accounts waiting for approval are listed first.
    </p>

    <table border="1">
        <tr>
            <th>Username</th>
            <th>Name</th>
            <th>Lab</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Registered</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($u = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars(trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''))) ?></td>
                <td><?= htmlspecialchars($u['lab'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= $u['approved'] ? 'Approved' : 'Pending approval' ?></td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No users are assigned to you yet.</td></tr>
        <?php endif; ?>
    </table>

    <a href="/admin.php">Back to admin page</a>
</main>
</body>
</html>
<?php
$stmt->close();
$link->close();
?>