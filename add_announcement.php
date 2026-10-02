<?php

// Start session so that the homepage knows whether a user is logged in
session_start();

// Include database link
include 'db.php';

// All announcements, newest first
$announcements = $link->query(
    "SELECT announcement_id, title, message, author_name, created_at
     FROM announcements
     ORDER BY created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reagently - Announcements</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
// Main header and navigation bar
include "navbar.php";
?>

<main class="page">
    <h2>Add Announcement</h2>

    <?php if (isset($_GET['msg'])): ?>
        <p><strong><?= htmlspecialchars($_GET['msg']) ?></strong></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;"><strong><?= htmlspecialchars($_GET['error']) ?></strong></p>
    <?php endif; ?>

    <form action="insert_announcement.php" method="POST">
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

    <table border="1">
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
                    <form action="delete_announcement.php" method="POST"
                          onsubmit="return confirm('Delete this announcement?');">
                        <input type="hidden" name="announcement_id"
                               value="<?= (int)$a['announcement_id'] ?>">
                        <input type="submit" value="Delete">
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No announcements yet.</td></tr>
        <?php endif; ?>
    </table>

    <a href="admin.php">Back to admin page</a>
</main>
</body>
</html>
<?php $link->close(); ?>