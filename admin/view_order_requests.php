<?php
/*
View Order Requests
Allows administrators to view reagent order requests submitted by users,
and to accept or deny each request. The user is notified by email.
*/

// Start session and set base path variable
require_once __DIR__ . "/../init.php";
use function Email\sendEmail;

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "authentication/login.php");
    exit;
}

// Only admins can access this page
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Access denied. Admin permission is required.");
}

// Include database connection
include BASE_PATH . '/db.php';


/* =========================
   Admin clicked "Accept" or "Deny"
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_id = (int)($_POST['request_id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $msg   = '';
    $error = '';

    // Look up everything from the database using only the request id,
    // instead of trusting values sent by the form.
    $sql_req =
    "SELECT
        order_requests.reagent_name,
        users.username,
        users.email

     FROM order_requests

     JOIN users
     ON order_requests.user_id = users.user_id

     WHERE order_requests.request_id = ?";

    $stmt = $link->prepare($sql_req);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $mail_file = BASE_PATH . '/send_email.php';

    if (!in_array($action, ['accept', 'deny'], true)) {

        $error = "Unknown action.";

    } elseif (!$request) {

        $error = "Request not found. It may already have been handled.";

    } elseif (!is_file($mail_file)) {

        $error = "Email is not set up (send_email.php was not found).";

    } else {

        // Load the email code only when it is needed, so that a problem
        // with email can never break the list page itself.
        require_once $mail_file;

        $username     = htmlspecialchars($request['username']);
        $reagent_name = htmlspecialchars($request['reagent_name']);

        if ($action === 'accept') {

            $url     = BASE_URL . "user/make_reservation.php";
            $subject = "Order request accepted";

            $body = "
                <h2>Hi $username,</h2>
                <p>An admin has accepted your order request for the reagent
                   \"$reagent_name\".</p>
                <p>You can check its availability and reserve it for your project
                   by clicking the button below:</p>
                <p><a href=\"" . htmlspecialchars($url) . "\" style=\"background:#222;color:white;padding:12px 24px;
                text-decoration:none;border-radius:4px;\">Make Reservation</a></p>
            ";

            $done_text = "Order accepted and user notified.";

        } else {

            $subject = "Order request denied";

            $body = "
                <h2>Hi $username,</h2>
                <p>Unfortunately, an admin has denied your order request for the
                   reagent \"$reagent_name\".</p>
                <p>If you have questions, please contact your lab admin.</p>
            ";

            $done_text = "Order denied and user notified.";
        }

        try {
            $sent = sendEmail($request['email'], $request['username'], $subject, $body);
        } catch (Throwable $e) {
            $sent = false;
        }

        if ($sent) {

            // Remove the order request only after the email was sent
            $stmt = $link->prepare("DELETE FROM order_requests WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();

            $msg = $done_text;

        } else {

            $error = "Unable to notify the user. The request was kept.";
        }
    }

    // Redirect so that refreshing the page does not resend the form
    $query = $error !== '' ? "error=" . urlencode($error) : "msg=" . urlencode($msg);
    header("Location: " . BASE_URL . "admin/view_order_requests.php?" . $query);
    exit;
}

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';


/* =========================
   Get order requests
   ========================= */

// JOIN users is used so that the admin can see who submitted each request.
$sql =
"SELECT

    order_requests.request_id,
    order_requests.reagent_name,
    order_requests.request_text,
    order_requests.created_at,

    users.username,
    users.firstname,
    users.lastname,
    users.lab

FROM order_requests
JOIN users ON order_requests.user_id = users.user_id
ORDER BY order_requests.created_at DESC";

$result = $link->query($sql);

?>
<!DOCTYPE html>
<html>
<head>

    <!-- For usability on larger and smaller screens: -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Order Requests</title>

    <!-- Use the existing stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

    <h2>Requests of Orders</h2>

    <p>Reagent order requests submitted by users.</p>

    <?php if ($msg !== ''): ?>
        <h5 style="color:darkgreen"><?= htmlspecialchars($msg) ?></h5>
    <?php elseif ($error !== ''): ?>
        <h5 style="color:red"><?= htmlspecialchars($error) ?></h5>
    <?php else: ?>
        <br>
    <?php endif; ?>

    <!-- Order request table -->
    <table class="table">

        <tr>
            <th>User</th>
            <th>Name</th>
            <th>Lab Group</th>
            <th>Reagent</th>
            <th>Request</th>
            <th>Date</th>
            <th>Action</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row["username"]) ?></td>
                <td><?= htmlspecialchars(($row["firstname"] ?? "") . " " . ($row["lastname"] ?? "")) ?></td>
                <td><?= htmlspecialchars($row["lab"] ?? "") ?></td>
                <td><?= htmlspecialchars($row["reagent_name"]) ?></td>
                <td><?= htmlspecialchars($row["request_text"] ?? "") ?></td>
                <td><?= htmlspecialchars($row["created_at"]) ?></td>
                <td>
                    <!-- Accept the order and notify the user -->
                    <form action="<?= BASE_URL ?>admin/view_order_requests.php"
                          method="POST" style="display:inline;">
                        <input type="hidden" name="request_id"
                               value="<?= (int)$row['request_id'] ?>">
                        <input type="hidden" name="action" value="accept">
                        <input class="button" type="submit" value="Accept and notify">
                    <br><br>
                    </form>

                    <!-- Deny the order and notify the user -->
                    <form action="<?= BASE_URL ?>admin/view_order_requests.php"
                          method="POST" style="display:inline;"
                          onsubmit="return confirm('Deny this order request and notify the user?');">
                        <input type="hidden" name="request_id"
                               value="<?= (int)$row['request_id'] ?>">
                        <input type="hidden" name="action" value="deny">
                        <input class="button" type="submit" value="Deny and notify">
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>

        <?php else: ?>
            <tr>
                <td colspan="7">No order requests found.</td>
            </tr>
        <?php endif; ?>

    </table>

    <br><br>

    <a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>

</main>
</body>
</html>
<?php
// Close database connection
$link->close();
?>