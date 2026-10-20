<?php
// Manage company administrator assignments and permissions.
require_once __DIR__ . '/init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/platform_auth.php';
require_platform_admin($link);

$candidates = $link->query(
    "SELECT u.user_id, u.username, u.email, u.approved, u.company_id, c.company_name
     FROM users u JOIN companies c ON c.company_id = u.company_id
     WHERE u.role = 'user' AND u.is_verified = 1 AND c.status = 'ACTIVE'
     ORDER BY c.company_name, u.username"
);
$admins = $link->query(
    "SELECT u.user_id, u.username, u.email, c.company_name, c.status
     FROM users u JOIN companies c ON c.company_id = u.company_id
     WHERE u.role = 'admin' ORDER BY c.company_name, u.username"
);
$csrf = platform_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reagently - Company Admins</title><link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
<h1>Manage Company Administrators</h1>
<p>Assign and manage administrator permissions for users in active companies.</p>
<?php if (isset($_GET['msg'])): ?><p><?= htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if (isset($_GET['error'])): ?><p style="color:red"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<h2>Assign Company Admin</h2>
<?php if ($candidates->num_rows === 0): ?>
<p>No eligible verified users are currently available.</p>
<?php else: ?>
<form method="POST" action="<?= BASE_URL ?>db_connections/assign_company_admin.php">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<label>Verified user
<select name="user_id" required>
<?php while ($u = $candidates->fetch_assoc()): ?>
<option value="<?= (int)$u['user_id'] ?>">
<?= htmlspecialchars($u['company_name'] . ' — ' . $u['username'] . ' (' . $u['email'] . ')' .
     ((int)$u['approved'] === 1 ? ' [approved]' : ' [pending]'), ENT_QUOTES, 'UTF-8') ?>
</option>
<?php endwhile; ?>
</select></label>
<button class="button" type="submit">Assign Admin</button>
</form>
<?php endif; ?>
<h2>Current Company Admins</h2>
<p>Manage existing company administrators. At least one administrator must remain assigned to each company.</p>
<table class="table"><thead><tr><th>Company</th><th>Status</th><th>Username</th><th>Email</th><th>Actions</th></tr></thead><tbody>
<?php while ($a = $admins->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($a['company_name'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($a['status'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($a['username'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8') ?></td>
<td>
<form method="POST" action="<?= BASE_URL ?>db_connections/remove_company_admin.php"
      onsubmit="return confirm('Remove company administrator permissions from this user? The account will remain active as a normal user.');">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="user_id" value="<?= (int)$a['user_id'] ?>">
<button class="button" type="submit">Remove Admin</button>
</form>
</td></tr>
<?php endwhile; ?>
</tbody></table>
<a class="button" href="<?= BASE_URL ?>platform_admin.php">Back to Platform Administration</a>
</main></body></html>
<?php $link->close(); ?>
