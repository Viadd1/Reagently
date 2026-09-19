<?php

// Start the session so that we can check
// the currently logged-in user's role
session_start();


// If the user is not logged in,
// redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


// Only admins can access this page
if (
    !isset($_SESSION['role'])
    ||
    $_SESSION['role'] !== 'admin'
) {

    die(
        "Access denied. Admin permission is required."
    );
}

?>


<!DOCTYPE html>

<html>

<head>

    <title>
        Reagently - Admin
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<header>

    <h1>Reagently</h1>

</header>


<nav class="navbar">

    <a href="index.php">
        Home
    </a>

    <a href="view_reagents.php">
        View reagents
    </a>

    <a href="user.php">
        User
    </a>

    <a href="admin.php">
        Admin
    </a>

    <a href="logout.php">
        Log out
    </a>

</nav>


<main class="page">


    <h2>Admin Functions</h2>


    <p>
        Manage reagents, storage locations,
        projects and users.
    </p>


    <br>


    <h3>Reagent Management</h3>


    <a
        class="button"
        href="add_reagent.php"
    >
        Add Reagent
    </a>


    <a
        class="button"
        href="add_lot.php"
    >
        Add Reagent Lot
    </a>


    <a
        class="button"
        href="add_location.php"
    >
        Add Location
    </a>


    <br><br>


    <h3>Project Management</h3>


    <a
        class="button"
        href="add_project.php"
    >
        Create Project
    </a>


    <a
        class="button"
        href="add_project_member.php"
    >
        Add Project Member
    </a>


    <br><br>


    <h3>Inventory Management</h3>


    <a
        class="button"
        href="view_reagents.php"
    >
        View Inventory
    </a>


    <a
        class="button"
        href="transactions.php"
    >
        Inventory History
    </a>


</main>


</body>

</html>