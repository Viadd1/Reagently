<!-- View Order Requests 
 Allows administrators to view reagent order requests submitted by users. -->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";
require_once BASE_PATH . '/send_email.php';
use function Email\sendEmail;

echo BASE_URL;
// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /authentication/login.php");
    exit;
}

// Only admins can access this page
if (
    !isset($_SESSION['role'])
    ||
    $_SESSION['role'] !== 'admin'
) {
    die(
        "Access denied. Admin permission is required."
    );
}

// Include database connection
include BASE_PATH . '/db.php';

// If admin has clicked "notify user"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = $_POST["username"];
    $request_id = $_POST["request_id"];
    $reagent_name = $_POST["reagent_name"];

    $sql_email = "SELECT email
                  FROM users
                  WHERE username = ?";
    
    $stmt = $link->prepare($sql_email);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {

        $email = $row["email"];
    }

    // Send email to the user
    $url = BASE_URL . "/localhost/user/make_reservation.php";

    $subject = "Order request accepted";

    $body = "
        <h2>Hi \"$username\",</h2>
        <p>An admin has just confirmed that your requested reagent, \"$reagent_name\", is now in stock.</p>
        <p>If you still need it for your project, click the button below to reserve it:</p>
        <p><a href=\"$url\" style=\"background:#222;color:white;padding:12px 24px;
        text-decoration:none;border-radius:4px;\">Make Reservation</a></p>
    ";

    if(sendEmail($email, $username, $subject, $body)) {

        $email_msg = "User has been notified.";
    } else {

        $email_msg = "Unable to notify user";
    }

    // Remove the order request
    $sql_del = "DELETE FROM order_requests
                WHERE request_id = ?";
    
    $stmt = $link->prepare($sql_del);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

}


// Get order requests
// Get all submitted order requests. JOIN users is used so that the admin can see who submitted each request.
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

FROM order_requests JOIN users ON order_requests.user_id = users.user_id
ORDER BY order_requests.created_at DESC";

// Execute query
$result = $link->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            echo $email_msg;

        } else {

            echo "<br>";
        }
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

                // Button for notifying user when it's in stock

                echo "<td>" .

                "<form action=" . BASE_URL . "admin/view_order_requests.php
                    method='POST'
                    style='display:inline;'
                    >
                    <input 
                    type='hidden' 
                    name='username' 
                    value='" . 
                    htmlspecialchars($row['username']) . 
                    "'>
                    <input 
                    type='hidden' 
                    name='request_id' 
                    value='" . 
                    htmlspecialchars($row['request_id']) . 
                    "'>
                    <input 
                    type='hidden' 
                    name='reagent_name' 
                    value='" . 
                    htmlspecialchars($row['reagent_name']) . 
                    "'>
                    <input class='button'
                    type='submit'
                    value='Notify user'>
                </form>" .
                
                "</td>";

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
$link->close();
?>