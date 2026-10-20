<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_csrf_token();
$email_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    tenant_require_post_csrf();
    $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if (!is_int($requestId)) { http_response_code(400); exit('Invalid order request ID.'); }
    // All recipient details are looked up by request ID and current company.
    $stmt = $link->prepare(
        'SELECT o.request_id, o.reagent_name, u.username, u.email FROM order_requests o
         JOIN users u ON o.user_id = u.user_id
         WHERE o.request_id = ? AND u.company_id = ?'
    );
    $stmt->bind_param('ii', $requestId, $companyId);
    $stmt->execute(); $recipient = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$recipient) {
        $email_msg = 'Order request not found in your company.';
    } else {
        require_once BASE_PATH . '/send_email.php';
        $safeName = htmlspecialchars($recipient['username'], ENT_QUOTES, 'UTF-8');
        $safeReagent = htmlspecialchars($recipient['reagent_name'], ENT_QUOTES, 'UTF-8');
        $body = '<p>Hi ' . $safeName . ',</p><p>Your requested reagent, ' . $safeReagent .
                ', has been marked available. Sign in to Reagently to make a reservation.</p>';
        try {
            $sent = \Email\sendEmail($recipient['email'], $recipient['username'], 'Order request accepted', $body);
        } catch (Throwable $e) {
            error_log('Order request email failed: ' . $e->getMessage()); $sent=false;
        }
        if ($sent) {
            $stmt = $link->prepare(
                'DELETE o FROM order_requests o JOIN users u ON o.user_id = u.user_id
                 WHERE o.request_id = ? AND u.company_id = ?'
            );
            $stmt->bind_param('ii', $requestId, $companyId);
            $stmt->execute(); $stmt->close();
            $email_msg = 'User notified; order request archived from the pending list.';
        } else {
            $email_msg = 'Email failed. The request remains in the pending list.';
        }
    }
}
$stmt = $link->prepare(
    'SELECT o.request_id, o.reagent_name, o.request_text, o.created_at,
            u.username, u.firstname, u.lastname, u.lab
     FROM order_requests o JOIN users u ON o.user_id = u.user_id
     WHERE u.company_id = ? ORDER BY o.created_at DESC'
);
$stmt->bind_param('i', $companyId);
$stmt->execute(); $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Order Requests</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>
<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

    <h2>Requests of Orders</h2>

    <p>
        Reagent order requests submitted by users.
    </p>

    <h5 style="color:darkgreen">

        <?php 
        echo htmlspecialchars($email_msg, ENT_QUOTES, 'UTF-8');
    ?>

    </h5>

    <!-- Order request table -->
    <table class="table">

        <tr>

            <th>User</th>

            <th>Name</th>

            <th>Lab Group</th>

            <th>Reagent</th>

            <th>Request</th>

            <th>Date</th>

            <th>In stock?</th>

        </tr>

        <?php

        // Check whether any order requests exist
        if ($result->num_rows > 0) {

            // Display one row for each request
            while (
                $row =
                $result->fetch_assoc()
            ) {
                echo "<tr>";

                // Username
                echo "<td>" .

                htmlspecialchars(
                    $row["username"]
                ) .

                "</td>";

                // Full name
                echo "<td>" .

                htmlspecialchars(
                    ($row["firstname"] ?? "") .
                    " " .
                    ($row["lastname"] ?? "")
                ) .

                "</td>";

                // Lab group
                echo "<td>" .

                htmlspecialchars(
                    $row["lab"] ?? ""
                ) .

                "</td>";

                // Requested reagent
                echo "<td>" .

                htmlspecialchars(
                    $row["reagent_name"]
                ) .

                "</td>";

                // Request message
                echo "<td>" .

                htmlspecialchars(
                    $row["request_text"]
                ) .

                "</td>";

                // Date submitted
                echo "<td>" .

                htmlspecialchars(
                    $row["created_at"]
                ) .

                "</td>";

                echo '<td><form method="POST" action="' . BASE_URL . 'admin/view_order_requests.php">'
                   . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') . '">'
                   . '<input type="hidden" name="request_id" value="' . (int)$row['request_id'] . '">'
                   . '<button class="button" type="submit">Notify user</button></form></td>';

                echo "</tr>";
            }

        } else {

            // No order requests have been submitted yet
            echo
            "<tr>

                <td colspan='7'>
                    No order requests found.
                </td>

            </tr>";
        }
        ?>

    </table>

    <br><br>

    <a
        class="button"
        href="<?= BASE_URL ?>admin.php"
    >
        Back to Admin
    </a>

</main>
</body>
</html>

<?php
// Close database connection
$stmt->close(); $link->close();
?>