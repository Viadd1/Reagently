<!--
Displays the login form where users enter their username and password.
The form sends the login information to login_process.php.
-->

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

<!-- Main page content -->
<main class="page" style="margin-top: -50px;">

    <h2>Login to Reagently here!</h2>

    <!-- Login form -->
    <form
        class="form-card"
        action="login_process.php"
        method="POST"
    >

        <!-- Username -->
        <div class="form-group">

            <h3>
            <label>
                Username
            </label>
            </h3>

            <input
                type="text"
                name="username"
                required
            >
            <br>
        
        </div>

        <br>

        <!-- Password -->
        <div class="form-group">

            <h3>    
            <label>
                Password
            </label>
            </h3>

            <input
                type="password"
                name="password"
                required
            >
            <br>
        
        </div>

        <br><br>
        <label>
            Verification code:
        </label>
        <br><br>
        <img
            src="captcha.php"
            id="captchaImage"
            alt="CAPTCHA"
        >
        <br>
        <!-- Refresh CAPTCHA without refreshing the whole page -->
         <button
                type="button"
                onclick="document.getElementById('captchaImage').src ='captcha.php?' + Date.now();" >
                Refresh
        </button>

        <br><br>

        <input
            type="text"
            name="captcha"
            placeholder="Enter the code above"
            required
            autocomplete="off" >
        
        <br><br>


        <!-- Login button -->
        <br>
        <input
            class="button"
            type="submit"
            value="Login"
        >

    </form>

    <p>
        Don't have an account?
        <a href="register.php">
            Register here
        </a>
    </p>

</main>

</body>

</html>