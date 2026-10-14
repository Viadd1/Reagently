<?php

/*
Main administration page for Reagently.
Only users with the admin role can access this page and use the available reagent, project and inventory management functions.
*/

// Start the session so that we can check the currently logged-in user's role
require_once "init.php";

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

    <title>Reagently - Admin</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>


<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

<?php include BASE_PATH . "/expiry_notice.php"; ?>


    <h2>Admin Functions</h2>


    <p>
        Manage reagents, storage locations,
        projects and users.
    </p>


    <br>

    <h3>Announcements</h3>

    <a 
        class="button" 
        href="/admin/add_announcement.php"
    >
        Add Announcement
    </a>

<br><br>


    <h3>Reagent Management</h3>


    <a
        class="button"
        href="/admin/add_reagent.php"
    >
        Add Reagent
    </a>


    <a
        class="button"
        href="/admin/add_location.php"
    >
        Add Location
    </a>

    <a
        class="button"
        href="/admin/add_lot.php"
    >
        Add Reagent Lot
    </a>

    <a
        class="button"
        href="/admin/import_csv.php"
    >
        Import from CSV
    </a>

    <a 
        class="button" 
        href="/admin/manage_reagents.php"
        
    >
        Manage Reagents
    </a>

    <br><br>

    <h3>Project Management</h3>

    <a
        class="button"
        href="/admin/add_project.php"
    >
        Create Project
    </a>

    <a
        class="button"
        href="/admin/add_project_member.php"
    >
        Add Project Member
    </a>

    <br><br>

    <h3>Inventory Management</h3>

    <a
        class="button"
        href="/user/transactions.php"
    >
        Inventory History
    </a>

    
    <a
        class="button"
        href="/admin/view_order_requests.php"
    >
        View Order Requests
    </a>
    

    <a
        class="button"
        href="/admin/expiring_reagents.php"
    >
        Expiring Reagents
    </a>

    <br><br>

    <h3>User Management</h3>

    <a
        class="button"
        href="/admin/approve_users.php"
    >
        Approve New Users
    </a>

    <a
        class="button"
        href="/admin/view_users.php"
    >
        Administer Users
    </a>


</main>

</body>

</html>