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
$stmt = $link->prepare('SELECT announcement_id, title, message, author_name, created_at FROM announcements WHERE company_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $companyId);
$stmt->execute();
$announcements = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Announcements</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php"; 
?>

<main class="page">
    <h2>Add Announcement</h2>

    <?php if (isset($_GET['msg'])): ?>
        <p><strong><?= htmlspecialchars($_GET['msg']) ?></strong></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;"><strong><?= htmlspecialchars($_GET['error']) ?></strong></p>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>db_connections/insert_announcement.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <label>Title:
            <input type="text" name="title" maxlength="150" required>
        </label>
        <br><br>
        <label>Message:<br>
            <textarea name="message" rows="6" cols="50" required></textarea>
        </label>
        <br><br>
        <input class="button" type="submit" value="Post Announcement">
    </form>

    <h3>Existing Announcements</h3>

    <table class="table">
        <tr>
            <th>Date</th>
            <th>Title</th>
            <th>Message</th>
            <th>Author</th>
            <th>Action</th>
        </tr>

        <?php if ($announcements->num_rows > 0): ?>
            <?php while ($a = $announcements->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($a['created_at']) ?></td>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= nl2br(htmlspecialchars($a['message'])) ?></td>
                <td><?= htmlspecialchars($a['author_name']) ?></td>
                <td>
                    <form action="<?= BASE_URL ?>db_connections/delete_announcement.php" method="POST"
                          onsubmit="return confirm('Delete this announcement?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="announcement_id"
                               value="<?= (int)$a['announcement_id'] ?>">
                        <input class="button" type="submit" value="Delete">
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No announcements yet.</td></tr>
        <?php endif; ?>
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
<?php $stmt->close(); $link->close(); ?>