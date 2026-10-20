<?php
// Company-scoped approval page. Only an ACTIVE company's admin can approve its users.
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/company_auth.php';
$companyId = require_company_admin($link);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    company_require_csrf();
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$userId || $userId < 1) company_go('error', 'Invalid user ID.');
    try {
        $stmt = $link->prepare("UPDATE users SET approved = 1
                                WHERE user_id = ? AND company_id = ? AND role = 'user'
                                  AND approved = 0 AND is_verified = 1");
        $stmt->bind_param('ii', $userId, $companyId);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
        if ($changed !== 1) {
            company_go('error', 'User not eligible for approval.');
        }

        // Optional notification: user approval must not fail because SMTP is unavailable.
        $emailSent = false;
        if (is_file(BASE_PATH . '/vendor/autoload.php') && is_file(BASE_PATH . '/.env')) {
            try {
                $userStmt = $link->prepare('SELECT username, email FROM users WHERE user_id = ? AND company_id = ?');
                $userStmt->bind_param('ii', $userId, $companyId);
                $userStmt->execute();
                $recipient = $userStmt->get_result()->fetch_assoc();
                $userStmt->close();
                if ($recipient) {
                    require_once BASE_PATH . '/send_email.php';
                    $safeUsername = htmlspecialchars($recipient['username'], ENT_QUOTES, 'UTF-8');
                    $emailSent = \Email\sendEmail(
                        $recipient['email'], $recipient['username'], 'Reagently account approved',
                        '<p>Hi ' . $safeUsername . ',</p><p>Your Reagently account has been approved. You can now log in.</p>'
                    );
                }
            } catch (Throwable $error) {
                error_log('Approval notification failed: ' . $error->getMessage());
            }
        }
        company_go('msg', 'User approved in your company.' . ($emailSent ? ' Confirmation email sent.' : ' Email notification unavailable.'));
    } catch (mysqli_sql_exception $e) {
        company_go('error', 'Database error.');
    }
}

$stmt = $link->prepare("SELECT user_id, username, lab, firstname, lastname, email
                        FROM users
                        WHERE company_id = ? AND role = 'user'
                          AND approved = 0 AND is_verified = 1
                        ORDER BY lastname, username");
$stmt->bind_param('i', $companyId);
$stmt->execute();
$pending = $stmt->get_result();
$csrf = company_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reagently - Approve Users</title><link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
<h2>Approve Users — Your Company</h2>
<p>Only verified, unapproved regular users from your company are listed.</p>
<?php if (isset($_GET['msg'])): ?><p><?= htmlspecialchars((string)$_GET['msg'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if (isset($_GET['error'])): ?><p style="color:red"><?= htmlspecialchars((string)$_GET['error'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<table class="table">
<thead><tr><th>First name</th><th>Last name</th><th>Lab</th><th>Username</th><th>Email</th><th>Action</th></tr></thead>
<tbody>
<?php if ($pending->num_rows === 0): ?>
<tr><td colspan="6">No verified users awaiting approval in your company.</td></tr>
<?php else: ?>
<?php while ($u = $pending->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($u['firstname'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($u['lastname'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($u['lab'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
<td><form method="POST" action="<?= BASE_URL ?>admin/approve_users.php">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
<button class="button" type="submit">Approve user</button></form></td>
</tr>
<?php endwhile; endif; ?>
</tbody></table>
<a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>
</main></body></html>
<?php $stmt->close(); $link->close(); ?>
