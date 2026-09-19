<!--
Displays the registration form for new Reagently users.
The entered information is sent to insert_user.php.
-->

<!DOCTYPE html>

<html>

<head>

    <!-- Title displayed in the browser tab -->
    <title>Register - Reagently</title>

</head>


<body>


<!-- Main heading -->
<h1>Create an Account</h1>


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


    <!-- Submit registration form -->
    <input
        type="submit"
        value="Register"
    >


</form>


<br>


<!-- Link for users who already have an account -->
<a href="login.php">
Already have an account? Log in
</a>


<br><br>


<!-- Return to the home page -->
<a href="index.php">
Back to home
</a>


</body>

</html>