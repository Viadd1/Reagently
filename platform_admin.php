<?php
// Platform administration: company creation, administrator management, and access control.
require_once __DIR__ . '/init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/platform_auth.php';
require_platform_admin($link);

$sql = "SELECT c.company_id, c.company_name, c.status, c.created_at,
               COUNT(u.user_id) AS user_count,
               SUM(CASE WHEN u.role = 'admin' THEN 1 ELSE 0 END) AS admin_count
        FROM companies c
        LEFT JOIN users u ON u.company_id = c.company_id
        GROUP BY c.company_id, c.company_name, c.status, c.created_at
        ORDER BY c.company_id";
$companies = $link->query($sql);
$csrf = platform_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reagently - Platform Admin</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <h1>Reagently Platform Administration</h1>
    <p>Manage companies, administrator assignments, and company access.</p>
    <?php if (isset($_GET['msg'])): ?>
        <p><?= htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h2>Create Company</h2>
    <form method="POST" action="<?= BASE_URL ?>db_connections/create_company.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label>Company name <input type="text" name="company_name" maxlength="255" required></label>
        <button class="button" type="submit">Create Company</button>
    </form>
    <p>New companies start with Pending status. Activate them when they are ready.</p>

    <h2>Company Administrators</h2>
    <p><a class="button" href="<?= BASE_URL ?>manage_company_admins.php">Manage Company Admins</a></p>

    <h2>Companies</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Company</th><th>Status</th><th>Users</th><th>Company Admins</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($c = $companies->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$c['company_id'] ?></td>
                <td><?= htmlspecialchars($c['company_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$c['user_count'] ?></td>
                <td><?= (int)$c['admin_count'] ?></td>
                <td>
                    <?php if ($c['status'] !== 'ACTIVE'): ?>
                        <form method="POST" action="<?= BASE_URL ?>db_connections/change_company_status.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="company_id" value="<?= (int)$c['company_id'] ?>">
                            <input type="hidden" name="new_status" value="ACTIVE">
                            <button class="button" type="submit">Activate</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($c['status'] !== 'SUSPENDED'): ?>
                        <form method="POST" action="<?= BASE_URL ?>db_connections/change_company_status.php"
                              onsubmit="return confirm('Suspend this company? Its users may lose access.');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="company_id" value="<?= (int)$c['company_id'] ?>">
                            <input type="hidden" name="new_status" value="SUSPENDED">
                            <button class="button" type="submit">Suspend</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
<?php $link->close(); ?>
