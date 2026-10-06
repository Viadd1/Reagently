<!--
Displays the login form where users enter their username and password.
The form sends the login information to login_process.php.
-->

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Login</title>

    <!-- Use the common stylesheet -->
    <link rel="stylesheet" href="style.css">

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