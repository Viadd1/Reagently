<!--
Displays the registration form for new Reagently users.
The entered information is sent to insert_user.php.
-->

<!DOCTYPE html>

<html>

<head>

    <!-- Title displayed in the browser tab -->
    <title>Register - Reagently</title>
    
    <!-- Use existing website design -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<!-- Website title -->

<header>

    <h1>Reagently</h1>

</header>

<!-- Navigation bar -->

<nav class="navbar">

    <a href="index.php">
        Home
    </a>

    <?php

    // Show these links only when a user is logged in
    if (isset($_SESSION['user_id'])) {

    ?>

        <a href="view_reagents.php">
            View reagents
        </a>

        <a href="user.php">
            User
        </a>

        <?php

        // Only admins see the Admin link
        if (
            isset($_SESSION['role'])
            &&
            $_SESSION['role'] === 'admin'
        ) {

        ?>

            <a href="admin.php">
                Admin
            </a>

        <?php

        }

        ?>

        <a href="logout.php">
            Log out
        </a>

    <?php

    } else {

    ?>

        <!-- Show Login and Register when nobody is logged in -->

        <a href="login.php">
            Login
        </a>

        <a href="register.php">
            Register
        </a>

    <?php

    }

    ?>

</nav>

<!-- Main heading -->
<main class="page">
    <h2>Register to Reagently here!</h2>
    <h3>Create an account</h3>

<!--
    Registration form

    The entered information will be sent to
    insert_user.php using the POST method.
-->
    <form
    action="insert_user.php"
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

    <!--
        Email address

        type="email" allows the browser to perform
        basic email-format validation.
    -->
    Email:

    <input
        type="email"
        name="email"
        required
    >

    <br><br>

    <!--
        Password field

        type="password" hides the entered characters.
        The password will later be hashed in insert_user.php
        before being stored in the database.
    -->
    Password:

    <input
        type="password"
        name="password"
        required
    >

    <br><br>

    <!--
        Confirmation field

        This value is not stored in the database.
        insert_user.php will check that both passwords match.
    -->
    Confirm password:

    <input
        type="password"
        name="confirm_password"
        required
    >

    <br><br>

    <input
        class="button"
        type="submit"
        value="Register"
    >


</form>

<!-- Link for users who already have an account -->
<a href="login.php">
Already have an account? Log in
</a>

</main>

</body>

</html>