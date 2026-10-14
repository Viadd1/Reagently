<!-- Logout
Logs the current user out by clearing and destroying the session, then redirects the user to the login page. -->

<?php 

// Start the existing session
// We need to access the current session before we can remove it.
session_start();

// Remove all session variables
// This clears values such as:
// $_SESSION['user_id']
// $_SESSION['username']
// $_SESSION['role']
session_unset();

// Destroy the session completely
session_destroy();

// Redirect the user to the login page
header(
    "Location: login.php"
);

// Stop the script after redirecting
exit;
?>