<?php

// Start the session so that we can check the currently logged-in user's role
require_once "init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// Verify role, account approval, email, and company status against the database.
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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

<?php include BASE_PATH . "/admin/expiry_notice.php"; ?>


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

    <a
        class="button"
        href="/admin/manage_projects.php"
    >
        Manage Projects
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

        <a
        class="button"
        href="/admin/supervised_users.php"
    >
        My Users
    </a>

</main>
</body>
</html>