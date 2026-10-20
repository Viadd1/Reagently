<?php
/** Company administrator: view and search users in the current company. */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

// Check the logged-in user and their admin role against the database.
$companyId = tenant_require_admin($link);
$csrfToken = tenant_csrf_token();

// The search term is data, not SQL. Limit its length and bind it safely.
$searchQuery = $_GET['search_query'] ?? '';
if (!is_string($searchQuery)) {
    $searchQuery = '';
}
$searchQuery = substr(trim($searchQuery), 0, 100);
$like = '%' . $searchQuery . '%';

// Do not show users from other companies or the platform administrator.
$stmt = $link->prepare(
    "SELECT user_id, username, lab, firstname, lastname, email, approved
     FROM users
     WHERE company_id = ?
       AND role IN ('admin', 'user')
       AND (username LIKE ? OR firstname LIKE ? OR lastname LIKE ?)
     ORDER BY lastname, username"
);
$stmt->bind_param('isss', $companyId, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();

// Escape all user-controlled values before displaying them as HTML.
$h = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Administer Users</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>

<main class="page">
    <h2>View Users</h2>
    <p>Search and administer users in your company.</p>

    <form action="<?= BASE_URL ?>admin/view_users.php" method="GET">
        <input
            type="text"
            name="search_query"
            placeholder="Search name or username..."
            value="<?= $h($searchQuery) ?>"
        >
        <input type="submit" value="Submit" class="button">
    </form>

    <br>

    <table class="table">
        <thead>
            <tr>
                <th>First name</th>
                <th>Last name</th>
                <th>Lab group</th>
                <th>Username</th>
                <th>Email</th>
                <th>Approved</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $h($row['firstname']) ?></td>
                        <td><?= $h($row['lastname']) ?></td>
                        <td><?= $h($row['lab']) ?></td>
                        <td><?= $h($row['username']) ?></td>
                        <td><?= $h($row['email']) ?></td>
                        <td><?= (int)$row['approved'] === 1 ? 'Yes' : 'No' ?></td>
                        <td>
                            <form
                                action="<?= BASE_URL ?>db_connections/edit_user.php"
                                method="POST"
                                style="display:inline;"
                            >
                                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                                <input class="button" type="submit" value="Edit user">
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>
    <a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>
</main>
</body>
</html>
<?php
$stmt->close();
$link->close();
