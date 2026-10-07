<?php

/*
Creates a new user account using information submitted from register.php.
The password is checked and hashed before the user is added to the database.
*/

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// Include database connection
include 'db.php';

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Registration</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<?php
include "navbar.php";


// Fetch data submitted from register.php
$username =
    $_POST['username'];

$firstname =
    $_POST['firstname'];

$lastname =
    $_POST['lastname'];

$lab =
    $_POST['lab'];

$email =
    $_POST['email'];

$password =
    $_POST['password'];

$confirm_password =
    $_POST['confirm_password'];

/*
Check that user has not entered username 'null' or email null@null
This is not allowed because they are used as a dummy user for deleted accounts
*/

if ($username === 'null' or $email === 'null@null') {
    die("Invalid username or email.");
}

// Check whether the two entered passwords match
if ($password !== $confirm_password) {

    die(
        "Passwords do not match."
    );
}


// Hash the password before storing it in the database
// The original password should never be stored directly

$hashed_password =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


/*---------------
Before constructing and executing the SQL query for inserting users,
check if any users exist already in the database.
If no users exist, the first user becomes the admin.
Otherwise, the role is automatically assigned as user and approved=0.
---------------*/

$sql_check_users = "SELECT *
                    FROM users";

$users = $link->query($sql_check_users);

if ($users->num_rows == 0) {

    $role = "admin";
    $approved = 1;

    $sql = 
    "INSERT INTO users
    (
        username,
        firstname,
        lastname,
        lab,
        email,
        password,
        role,
        approved
    )

    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare SQL statement
    $stmt =
        $link->prepare($sql);


    $stmt->bind_param(
        "sssssssi",
        $username,
        $firstname,
        $lastname,
        $lab,
        $email,
        $hashed_password,
        $role,
        $approved
    );    

} else {

    $sql =
    "INSERT INTO users
    (
        username,
        firstname,
        lastname,
        lab,
        email,
        password
    )

    VALUES (?, ?, ?, ?, ?, ?)";

    // Prepare SQL statement
    $stmt =
        $link->prepare($sql);


    $stmt->bind_param(
        "ssssss",
        $username,
        $firstname,
        $lastname,
        $lab,
        $email,
        $hashed_password
    );
}

// Execute the INSERT statement
$result =
    $stmt->execute();


// Check whether registration was successful
if ($result) {
    echo "Account requested successfully.";

} else {

    // Display database error
    // For example, this may happen when an email address
    // already exists because email is UNIQUE.

    echo "Error: " .
         $stmt->error;
}

// Close prepared statement

$stmt->close();

// Close database connection

$link->close();

?>