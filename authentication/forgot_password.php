<?php

require_once __DIR__ . "/../init.php";
require_once BASE_PATH . '/send_email.php';
use function Email\sendEmail;

// Include database connection to check if email address exists in database
include BASE_PATH . "/db.php";

$email = $_POST['email'] ?? '';

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Login</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?=BASE_URL?>style.css"
    >

</head>

<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main>

<?php

    /*-----------
    If user has not entered page through a POST form,
    they entered from the login page, so we display the form.
    -----------*/

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        echo '<h2>Restore Password</h2>

        <p>
        To restore your password, enter the email linked to your account below.<br>
        We will send an email with further instructions.<br><br>

        <form action="forgot_password.php"
            method="POST">
        
            Email:
            <input type="text"
            size="30"
            name="email">

            <input class="button"
                type="submit"
                value="Send">

            ';

    // If they have submitted the form
    } else {

        // Ensure that email is of valid format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("$email is not a valid email address. <a href='forgot_password.php'>Try again</a>");
        }

        // Check that email address exists in database

        $sql = "SELECT user_id, username, email
                FROM users
                WHERE email = ?";
        
        $stmt = $link->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $stmt->close();

        if ($result->num_rows == 1) {

            while ($row = $result->fetch_assoc()) {
                $user_id = $row["user_id"];
                $username = $row["username"];
            }

            $token = bin2hex(random_bytes(32));

            // The token is only valid for 1 hour,
            // but we write +3 hour due to time zone difference
            $expiresAt = date('Y-m-d H:i:s', strtotime('+3 hour'));

            $sql_token = 'INSERT INTO password_resets (
                          user_id,
                          token,
                          expires_at) 
                          VALUES (?, ?, ?)';
            
            $stmt = $link->prepare($sql_token);
            $stmt->bind_param('iss', $user_id, $token, $expiresAt);
            $stmt->execute();

            $stmt->close();

            $url = BASE_URL . "/localhost/authentication/reset_password.php?token=" . $token;

            $subject = "Reset your Reagently password";

            $body = "
            <h2>Password Reset</h2>
            <p>Click the link below to set a new password. It expires in 1 hour.</p>
            <p><a href=\"$url\" style=\"background:#222;color:white;padding:12px 24px;
            text-decoration:none;border-radius:4px;\">Reset password</a></p>
            <p>If you did not request this, ignore this email.</p>
            ";
            sendEmail($email, $username, $subject, $body);
        }

        echo 'If your email is linked to an account, we have sent you a reset link.';
    } ?>

</main>

</body>

</html>

<?php

session_destroy();

$link->close();

?>