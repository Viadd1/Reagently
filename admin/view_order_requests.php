<?php
/**
 * Company-scoped order request management.
 * Keep both Accept and Deny actions introduced on main.
 * An order request is removed only after the email was sent successfully.
 */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

$companyId = tenant_require_admin($link);
$csrfToken = tenant_csrf_token();

function orderRequestRedirect(string $key, string $message): void
{
    header('Location: ' . BASE_URL . 'admin/view_order_requests.php?' . $key . '=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    tenant_require_post_csrf();

    $requestId = filter_var(
        $_POST['request_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $action = $_POST['action'] ?? null;

    if (!is_int($requestId) || !is_string($action) || !in_array($action, ['accept', 'deny'], true)) {
        orderRequestRedirect('error', 'Invalid order request or action.');
    }

    // Fetch recipient data from the database, not from untrusted POST fields.
    // The requester and, when linked, the reagent must belong to this company.
    try {
        $recipientStmt = $link->prepare(
            'SELECT o.request_id, o.reagent_name, u.username, u.email
             FROM order_requests AS o
             JOIN users AS u ON u.user_id = o.user_id
             LEFT JOIN reagents AS r ON r.reagent_id = o.reagent_id
             WHERE o.request_id = ?
               AND u.company_id = ?
               AND (o.reagent_id IS NULL OR r.company_id = ?)
             LIMIT 1'
        );
        $recipientStmt->bind_param('iii', $requestId, $companyId, $companyId);
        $recipientStmt->execute();
        $recipient = $recipientStmt->get_result()->fetch_assoc();
        $recipientStmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Order request lookup failed: ' . $e->getMessage());
        orderRequestRedirect('error', 'Database error.');
    }

    if (!$recipient) {
        orderRequestRedirect('error', 'Order request not found in your company.');
    }

    // Loading send_email.php also loads Composer and SMTP configuration.
    // Missing mail configuration must not crash the management page.
    if (
        !is_file(BASE_PATH . '/send_email.php') ||
        !is_file(BASE_PATH . '/vendor/autoload.php') ||
        !is_file(BASE_PATH . '/.env')
    ) {
        orderRequestRedirect('error', 'Email is not configured. The order request was kept.');
    }

    $safeName = htmlspecialchars($recipient['username'], ENT_QUOTES, 'UTF-8');
    $safeReagent = htmlspecialchars($recipient['reagent_name'], ENT_QUOTES, 'UTF-8');

    if ($action === 'accept') {
        $subject = 'Order request accepted';
        $reservationUrl = htmlspecialchars(
            BASE_URL . 'user/make_reservation.php',
            ENT_QUOTES,
            'UTF-8'
        );
        $body = '<p>Hi ' . $safeName . ',</p>'
              . '<p>Your order request for ' . $safeReagent . ' has been accepted.</p>'
              . '<p>Please check current availability before making a reservation.</p>'
              . '<p><a href="' . $reservationUrl . '">Make Reservation</a></p>';
    } else {
        $subject = 'Order request denied';
        $body = '<p>Hi ' . $safeName . ',</p>'
              . '<p>Your order request for ' . $safeReagent . ' has been denied.</p>'
              . '<p>Contact your lab administrator if you have questions.</p>';
    }

    try {
        require_once BASE_PATH . '/send_email.php';
        $sent = \Email\sendEmail(
            $recipient['email'],
            $recipient['username'],
            $subject,
            $body
        );
    } catch (Throwable $e) {
        error_log('Order request email failed: ' . $e->getMessage());
        $sent = false;
    }

    if (!$sent) {
        orderRequestRedirect('error', 'Unable to notify the user. The request was kept.');
    }

    // Delete only after successfully sending the email. Scope the DELETE too.
    try {
        $deleteStmt = $link->prepare(
            'DELETE o FROM order_requests AS o
             JOIN users AS u ON u.user_id = o.user_id
             LEFT JOIN reagents AS r ON r.reagent_id = o.reagent_id
             WHERE o.request_id = ?
               AND u.company_id = ?
               AND (o.reagent_id IS NULL OR r.company_id = ?)'
        );
        $deleteStmt->bind_param('iii', $requestId, $companyId, $companyId);
        $deleteStmt->execute();
        $deleted = $deleteStmt->affected_rows;
        $deleteStmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Order request delete failed: ' . $e->getMessage());
        orderRequestRedirect('error', 'Email sent, but the request could not be cleared.');
    }

    if ($deleted !== 1) {
        orderRequestRedirect('error', 'Email sent, but the request was already handled or could not be cleared.');
    }

    $completedAction = $action === 'accept' ? 'accepted' : 'denied';
    orderRequestRedirect('msg', 'Order request ' . $completedAction . ' and user notified.');
}

// Company-scoped listing; no requests from other companies are visible.
$listStmt = $link->prepare(
    'SELECT o.request_id, o.reagent_name, o.request_text, o.created_at,
            u.username, u.firstname, u.lastname, u.lab
     FROM order_requests AS o
     JOIN users AS u ON u.user_id = o.user_id
     LEFT JOIN reagents AS r ON r.reagent_id = o.reagent_id
     WHERE u.company_id = ?
       AND (o.reagent_id IS NULL OR r.company_id = ?)
     ORDER BY o.created_at DESC'
);
$listStmt->bind_param('ii', $companyId, $companyId);
$listStmt->execute();
$result = $listStmt->get_result();

$msg = is_string($_GET['msg'] ?? null) ? $_GET['msg'] : '';
$error = is_string($_GET['error'] ?? null) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Order Requests</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <h2>Requests of Orders</h2>
    <p>Reagent order requests submitted by users in your company.</p>

    <?php if ($msg !== ''): ?>
        <p style="color:darkgreen"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p style="color:red"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <table class="table">
        <tr>
            <th>User</th><th>Name</th><th>Lab Group</th>
            <th>Reagent</th><th>Request</th><th>Date</th><th>Action</th>
        </tr>

        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="7">No order requests found.</td></tr>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['lab'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['reagent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['request_text'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form action="<?= BASE_URL ?>admin/view_order_requests.php" method="POST" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= (int)$row['request_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="accept">
                            <button class="button" type="submit">Accept and notify</button>
                        </form>
                        <br><br>
                        <form action="<?= BASE_URL ?>admin/view_order_requests.php" method="POST" style="display:inline;"
                              onsubmit="return confirm('Deny this order request and notify the user?');">
                            <input type="hidden" name="request_id" value="<?= (int)$row['request_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="deny">
                            <button class="button" type="submit">Deny and notify</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </table>

    <br><br>
    <a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>
</main>
</body>
</html>
<?php
$listStmt->close();
$link->close();
