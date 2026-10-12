<?php
// Include database connection to check if email address exists in database
include "db.php";

$email = $_POST['email'] ?? '';

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Login</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<?php
// Main header and navigation bar
include "navbar.php";
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

        $sql = "SELECT email
                FROM users
                WHERE email = ?";
        
        $stmt = $link->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            die("Email is not linked to any account. <a href='forgot_password.php'>Try again</a>");
            
        }

        echo 'There will be code for sending emails here soon!';
    } ?>

</main>

</body>

</html>

<?php
$link->close();

?>