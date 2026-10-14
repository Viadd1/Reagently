<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /login.php");
    exit;
}

// Include database connection
include BASE_PATH . "/db.php";

// Ensure that user has entered the page by submitting a user ID
$user_id = $_POST['user_id'] ?? '';

if (!$user_id) {
    die("No user ID provided.");
}

// If the user has submitted new user information, save variables and prepare SQL statement
if (isset($_POST['uname'])) {
    
    $uname = 
        $_POST['uname'];
    
    $fname =
        $_POST['fname'];

    $lname =
        $_POST['lname'];

    $lab =
        $_POST['lab'];

    $email =
        $_POST['email'];
    
    $sql = "UPDATE users

            SET
            username = ?,
            lab = ?,
            firstname = ?,
            lastname = ?,
            email = ?
            
            WHERE user_id = ?
            ";
    
    $stmt = $link->prepare($sql);

    $stmt->bind_param(
    "sssssi",
    $uname,
    $lab,
    $fname,
    $lname,
    $email,
    $user_id
    );
    
    $stmt->execute();

    // Check whether the user has changed password and that the passwords match
    $password = 
        $_POST['password'] ?? '';

    $confirm_password = 
        $_POST['password'] ?? '';


    if ($password &&
        $confirm_password) {
        
        $password =
            $_POST['password'];

        $confirm_password =
            $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {

            die(
                "Passwords do not match."
            );

        }

        $hashed_password =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // If the user changed password, create new statement

        $stmt->close();

        $sql = "UPDATE users

            SET password = ?
            
            WHERE user_id = ?
            ";
    
        $stmt = $link->prepare($sql);

        $stmt->bind_param(
        "si",
        $hashed_password,
        $user_id
        );
        
        $stmt->execute();
    
    }

    // Check if an admin has changed the role of the user
    if(isset($_POST['role'])) {

        $stmt->close();

        $role = $_POST['role'];

        $sql = "UPDATE users

            SET role = ?

            WHERE user_id = ?";

        $stmt = $link->prepare($sql);

        $stmt->bind_param(
            "si",
            $role,
            $user_id
        );

        $stmt->execute();
    }

    $success_message = "User information updated successfully.";
}


// SQL query for fetching current user information
$sql = 
" SELECT
        user_id,
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

         $uname = $user_data["username"];
         $lab = $user_data["lab"];
         $fname = $user_data["firstname"];
         $lname = $user_data["lastname"];
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
        Reagently - Edit User
    </title>

    <!-- Use your existing website stylesheet -->
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

    <h2>Edit User</h2>

    <p>
        Edit user information or delete account.
    </p>
    
    <h5 style="color:darkgreen">

        <?php
        if (isset($_POST['uname'])) {

            echo $success_message;

        } else {

            echo "<br>";
        }
        ?>

    </h5>

    <form
    action="edit_user.php"
    method="POST"
    >
        
        <?php
        
        echo '<input type="hidden"
            id="user_id"
            name="user_id"
            value="' . 
            $user_id . 
            '"><br><br>';

        echo '<label for="uname">
            Username:
            </label>

            <input type="text"
            id="uname"
            name="uname"
            value="' . 
            $uname . 
            '" required><br><br>';
        
        echo '<label for="fname">
            First name:
            </label>

            <input type="text"
            id="fname"
            name="fname"
            value="' . 
            $fname . 
            '"><br><br>';
    
        echo '<label for="lname">
            Last name:
            </label>

            <input type="text"
            id="lname"
            name="lname"
            value="' . 
            $lname .
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
        
        if (
            isset($_SESSION['role'])
            &&
            $_SESSION['role'] === 'admin'
            ) {
            
            echo '<label for="role">
                Role:
                </label>

                <select
                id="role"
                name="role">
                <option value="admin">Admin</option>
                <option value="user">User</option>
                </select>
                <br><br>';
            }
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

        <input 
            class="button"
            type="submit"
            value="Submit">
        
    </form>

    <form action="delete_user.php" 
        method="POST"
        onsubmit="return confirm('Are you sure you want to delete this account?');">

        <?php
        echo '<input type="hidden"
            id="user_id"
            name="user_id"
            value="' . 
            $user_id . 
            '">';
        ?>
        
        <input
        class="button"
        type="submit"
        value="Delete account">
    </form>

    <br>
    
<a
    class="button"
    href="<?= BASE_URL ?>user.php"
>
    Back to User
</a>

<?php
// Close prepared statement

$stmt->close();

// Close database connection

$link->close();

?>