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

    <a href="index.php">Home</a>

    <a href="view_reagents.php">View reagents</a>

    <a href="user.php">User</a>

    <a href="admin.php">Admin</a>

</nav>


<!-- Main page content -->
<main class="page">


    <h2>Login</h2>


    <!-- Login form -->
    <form
        class="form-card"
        action="login_process.php"
        method="POST"
    >


        <!-- Username -->
        <div class="form-group">

            <label>
                Username
            </label>

            <input
                type="text"
                name="username"
                required
            >

        </div>


        <!-- Password -->
        <div class="form-group">

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                required
            >

        </div>


        <!-- Login button -->
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