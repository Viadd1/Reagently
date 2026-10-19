<!-- Page where the requests for new user accounts appear and can be approved.
Only users with the admin role can access this page. -->

<?php

// Start session, set base path variable and load email
require_once __DIR__ . "/../init.php";
require_once BASE_PATH . '/send_email.php';
use function Email\sendEmail;

// If the user is not logged in, redirect them to the login page
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

// Give default values when the page is opened if no user has been approved yet
$_GET['user_id'] =
    $_GET['user_id'] ?? '';

// If an account has been approved by the admin, update the user
if ($_GET['user_id']) {

    $user_id = $_GET['user_id'];

    $sql_upd = 
    "UPDATE users
    SET approved = 1
    WHERE user_id = ?";

    $stmt = $link->prepare($sql_upd);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $success_message = "User approved successfully.";

    $stmt->close();

    $sql_email = "SELECT username, email
                  FROM users
                  WHERE user_id = ?";
    
    $stmt = $link->prepare($sql_email);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 1) {
        while ($row = $res->fetch_assoc()) {
            $username = $row["username"];
            $email = $row["email"];
        }
    }

    $stmt->close();

    $subject = "Account approved";

    $url = BASE_URL . "/localhost/authentication/login.php";

    $body = "
        <h2>Hi \"$username\",</h2>
        <p>An admin has just approved your account at Reagently!
        <p>Click the button below to log in:</p>
        <p><a href=\"$url\" style=\"background:#222;color:white;padding:12px 24px;
        text-decoration:none;border-radius:4px;\">To Login page</a></p>
    ";

    if (sendEmail($email, $username, $subject, $body)) {
        $email_message = "Confirmation email sent to user.";
    } else {
        $email_message = "Unable to send confirmation email.";
    }
}

// SQL query that selects all users not yet approved by the admin.
$sql = 
" SELECT
        user_id,
        username,
        lab,
        firstname,
        lastname,
        email

    FROM users
    
    WHERE approved = 0

    AND is_verified = 1

    AND username NOT LIKE 'null'
    
    ORDER BY lastname";

$result = $link->query($sql);
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Approve Users</title>
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

    <h2>Approve Users</h2>
    
    <h5 style="color:darkgreen">

        <?php
        if ($_GET['user_id']) {

            echo $success_message . "<br>" .
            $email_message;

        } else {

            echo "<br>";
        }
        ?>
    </h5>

    <!-- Inventory table -->
    <table class="table">

        <tr>

            <th>First name</th>

            <th>Last name</th>

            <th>Lab group</th>

            <th>Username</th>

            <th>Email</th>

        </tr>

        <?php

        // Check whether any users exist
        if ($result->num_rows > 0) {

            // Display one row for each reagent lot
            while (
                $row =
                $result->fetch_assoc()
            ) {

                echo "<tr>";

                // First name
                echo "<td>" .

                htmlspecialchars(
                    $row["firstname"]
                ) .

                "</td>";

                // Last name
                echo "<td>" .

                htmlspecialchars(
                    $row["lastname"]
                ) .

                "</td>";

                // Lab group
                echo "<td>" .

                htmlspecialchars(
                    $row["lab"] ?? ""
                ) .

                "</td>";

                // Username
                echo "<td>" .

                htmlspecialchars(
                    $row["username"] ?? ""
                ) .

                "</td>";

                // Email address
                echo "<td>" .

                htmlspecialchars(
                    $row["email"] ?? ""
                ) . 

                "</td>";

                // Approve user button
                echo "<td>";

                echo
                "<a
                    class='button'
                    href='approve_users.php?user_id=" .
                    $row["user_id"] .
                    "'>
                    Approve user
                    </a>";
                
                echo "</td>";

                echo "</tr>";
            }

        } else {

            // Display this if there are no users in the database yet.
            echo
            "<tr>

                <td colspan='11'>
                    No new registered users.
                </td>

            </tr>";
        }
        ?>

    </table>

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