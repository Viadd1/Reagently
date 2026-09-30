<?php

session_start();

include "db.php";

$user_id = $_POST['user_id'] ?? '';

if (!$user_id) {
    die("No user ID provided.");
}

$sql = 
" SELECT
        username,
        lab,
        firstname,
        lastname,
        email

    FROM users
    
    WHERE user_id = ?
";

$stmt = $link->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    while ($user_data = $result->fetch_assoc()) {

         $username = $user_data["username"];
         $lab = $user_data["lab"];
         $firstname = $user_data["firstname"];
         $lastname = $user_data["lastname"];
         $email = $user_data["email"];

    }
}
else {
        echo "No user found";
    }


    ?>


<!DOCTYPE html>

<html>

<head>

    <title>
        Reagently - Edit Users
    </title>

    <!-- Use your existing website stylesheet -->
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


<main class="page">

    <h2>Edit User</h2>

    <p>
        Edit user information or delete account.
    </p>
    
    <br>

    <form
    action="/update_user.php"
    method="POST"
    >
        
        <?php

        echo '<label for="fname">
            First name:
            </label>

            <input type="text"
            id="fname"
            name="fname"
            value="' . 
            $firstname . 
            '"><br><br>';
    
        echo '<label for="lname">
            Last name:
            </label>

            <input type="text"
            id="lname"
            name="lname"
            value="' . 
            $lastname .
            '"><br><br>';

        echo '<label for="lab">
            Lab:
            </label>

            <input type="text"
            id="lab"
            name="lab"
            value="' . 
            $lab . 
            '"><br><br>';

        echo '<label for="email">
            Email:
            </label>

            <input type="text"
            id="email"
            name="email"
            value="' . 
            $email .
            '"><br><br>';
    
        ?>

        <label for="password">
            Change password:
            </label>

            <input 
            type="password"
            id="password"
            name="password">

        <br><br>

        <label for="confirm">
            Confirm password:
            </label>

            <input 
            type="password"
            id="confirm_password"
            name="confirm_password">

        <br><br>

        <input type="submit" value="Submit">
    </form>


<?php
// Close prepared statement

$stmt->close();

// Close database connection

$link->close();

?>