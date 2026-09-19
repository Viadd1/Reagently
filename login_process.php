<?php

/*
Checks the username and password submitted from login.php.
If the login is successful, the user ID, username and role are stored in the session.
*/

// Start or resume a session.
// This must be placed before any HTML or echo output.
session_start();


// Include database connection
include 'db.php';


// Get username and password submitted from login.php

$username =
    $_POST['username'];

$password =
    $_POST['password'];


// SQL statement
//
// Find the user whose username matches
// the username entered in the login form.
//
// We also retrieve:
// user_id   -> used to identify the logged-in user
// username  -> useful for displaying the user's name
// password  -> hashed password stored in the database
// role      -> used later to distinguish user and admin

$sql =
"SELECT
    user_id,
    username,
    password,
    role

 FROM users

 WHERE username = ?";


// Prepare SQL statement

$stmt =
    $link->prepare($sql);


// Bind username to the placeholder
//
// username is a string,
// therefore the type is "s".

$stmt->bind_param(
    "s",
    $username
);


// Execute the SELECT query

$stmt->execute();


// Get the query result

$result =
    $stmt->get_result();


// Check whether exactly one user was found

if ($result->num_rows === 1) {


    // Fetch the user's information
    $user =
        $result->fetch_assoc();


    // Verify the entered password against
    // the hashed password stored in the database

    if (
        password_verify(
            $password,
            $user['password']
        )
    ) {


        // Create a new session ID after login
        // for better session security

        session_regenerate_id(true);


        // Store important user information
        // in the session

        $_SESSION['user_id'] =
            $user['user_id'];

        $_SESSION['username'] =
            $user['username'];

        $_SESSION['role'] =
            $user['role'];


        // Login successful
        //
        // Redirect the user to the home page

        header(
            "Location: index.php"
        );

        exit;


    } else {


        // Password is incorrect
        //
        // We use a general error message instead of
        // telling the user whether the username
        // or password was incorrect.

        echo "Incorrect username or password.";

        echo "<br><br>";

        echo
        '<a href="login.php">
            Try again
        </a>';

    }


} else {


    // No matching username was found

    echo "Incorrect username or password.";

    echo "<br><br>";

    echo
    '<a href="login.php">
        Try again
    </a>';

}


// Close prepared statement

$stmt->close();


// Close database connection

$link->close();

?>