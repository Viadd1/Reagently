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
    <style>
        /* Platform Admin only: separate the three management areas. */
        .platform-sections {
            margin-top: 32px;
        }
        .platform-section {
            padding: 20px 0 28px;
        }
        .platform-section + .platform-section {
            margin-top: 32px;
            padding-top: 48px;
        }
        .platform-section h2 {
            margin-top: 0;
            margin-bottom: 26px;
        }
        .platform-section .platform-action {
            margin: 0;
        }
        .platform-section .platform-table-wrap {
            overflow-x: auto;
            margin-top: 26px;
        }
        .platform-section .platform-table-wrap .table {
            width: 100%;
        }
    </style>
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

    <div class="platform-sections">
        <section class="platform-section" aria-labelledby="create-company-heading">
            <h2 id="create-company-heading">Create Company</h2>
            <form method="POST" action="<?= BASE_URL ?>db_connections/create_company.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <label>Company name <input type="text" name="company_name" maxlength="255" required></label>
                <button class="button" type="submit">Create Company</button>
            </form>
        </section>

        <section class="platform-section" aria-labelledby="company-admins-heading">
            <h2 id="company-admins-heading">Company Administrators</h2>
            <p class="platform-action"><a class="button" href="<?= BASE_URL ?>manage_company_admins.php">Manage Company Admins</a></p>
        </section>

        <section class="platform-section" aria-labelledby="companies-heading">
            <h2 id="companies-heading">Companies</h2>
            <div class="platform-table-wrap">
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
            </div>
        </section>
    </div>
</main>
</body>
</html>
<?php $link->close(); ?>
