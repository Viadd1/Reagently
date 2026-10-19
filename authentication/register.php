<!-- Register 
Displays the registration form for new Reagently users.
The entered information is sent to insert_user.php. -->

<?php
require_once __DIR__ . '/../init.php';
include BASE_PATH . "/db.php";

// Admins that a new user can choose from
$admins = $link->query(
    "SELECT user_id, firstname, lastname, lab FROM users
     WHERE role = 'admin' AND approved = 1 ORDER BY lab, lastname");
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Register</title>
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

<!-- Main heading -->
<main class="page">
    <h2>Register to Reagently here!</h2>
    <h3>Create an account</h3>

<!-- Registration form
    The entered information will be sent to insert_user.php using the POST method. -->
    <form
    action="<?=BASE_URL?>db_connections/insert_user.php"
    method="POST"
    >

    <!-- Username used to identify the user in the system -->
    Username:

    <input
        type="text"
        name="username"
        required
    >

    <br><br>

    <!-- User's first name -->
    First name:

    <input
        type="text"
        name="firstname"
    >

    <br><br>

    <!-- User's last name -->
    Last name:

    <input
        type="text"
        name="lastname"
    >

    <br><br>

    <!-- Laboratory or research group -->
    Lab / Group:

    <input
        type="text"
        name="lab"
    >

    <br><br>

<?php if ($admins->num_rows > 0): ?>

        <!-- Admin who is responsible for this user and will approve the account -->
    Your lab admin:

    <select
        name="supervisor_id"
        required
    >
        <option value="" disabled selected>Select your admin</option>

        <?php while ($a = $admins->fetch_assoc()): ?>
            <option value="<?= (int)$a['user_id'] ?>">
                <?= htmlspecialchars(trim($a['firstname'] . ' ' . $a['lastname']) . ' (' . ($a['lab'] ?? '') . ')') ?>
            </option>
        <?php endwhile; ?>
    </select>

    <br><br>

 <?php endif; ?>

    <!-- Email address
        type="email" allows the browser to perform basic email-format validation. -->
    Email:

    <input
        type="email"
        name="email"
        required
    >

    <br><br>

    <!-- Password field
        type="password" hides the entered characters.
        The password will later be hashed in insert_user.php before being stored in the database. -->
    Password:

    <input
        type="password"
        name="password"
        minlength="8"
        required
    >

    <br><br>

    <!-- Confirmation field
        This value is not stored in the database.
        insert_user.php will check that both passwords match. -->
    Confirm password:

    <input
        type="password"
        name="confirm_password"
        required
    >

    <br><br>

    <input
        type="checkbox"
        required
    >

    I consent to Reagently storing and processing my data in accordance with GDPR.

    <br><br>

    <input
        class="button"
        type="submit"
        value="Register"
    >

</form>

<!-- Link for users who already have an account -->
 <p>

Already have an account?
<a href="login.php">
Log in
</a>
</p>

</main>
</body>
</html>

<?php $link->close(); ?>