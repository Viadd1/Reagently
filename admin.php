<?php

/*
Main administration page for Reagently.
Only users with the admin role can access this page and use the available reagent, project and inventory management functions.
*/

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

    <title>Reagently - Add location</title>

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

<main class="page">


    <h2>Admin Functions</h2>


    <p>
        Manage reagents, storage locations,
        projects and users.
    </p>


    <br>

    <h3>Announcements</h3>

    <a 
        class="button" 
        href="add_announcement.php"
    >
        Add Announcement
    </a>

<br><br>


    <h3>Reagent Management</h3>


    <a
        class="button"
        href="add_reagent.php"
    >
        Add Reagent
    </a>


    <a
        class="button"
        href="add_location.php"
    >
        Add Location
    </a>

    <a
        class="button"
        href="add_lot.php"
    >
        Add Reagent Lot
    </a>

    <a 
        class="button" 
        href="manage_reagents.php"
        
    >
        Manage Reagents
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
        href="transactions.php"
    >
        Inventory History
    </a>

    
    <a
        class="button"
        href="view_order_requests.php"
    >
        View Order Requests
    </a>

    <br><br>

    <h3>User Management</h3>

    <a
        class="button"
        href="approve_users.php"
    >
        Approve New Users
    </a>

    <a
        class="button"
        href="view_users.php"
    >
        Administer Users
    </a>


</main>

</body>

</html>