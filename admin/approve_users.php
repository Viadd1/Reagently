<?php

/*
Page where the requests for new user accounts appear and can be approved.
Only users with the admin role can access this page.
*/

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /login.php");
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

// Include database connection
include BASE_PATH . '/db.php';

// Give default values when the page is opened if no user has been approved yet
$_GET['user_id'] =
    $_GET['user_id'] ?? '';

// If an account has been approved by the admin, update the user
if ($_GET['user_id']) {

    $approved_user = $_GET['user_id'];

    $sql_upd = 
    "UPDATE users
    
    SET approved = 1
    
    WHERE user_id = ?";

    $stmt = $link->prepare($sql_upd);

    $stmt->bind_param(
    "i",
    $approved_user
    );

    // Execute query
    $stmt->execute();

    // Get query result
    $result =
        $stmt->get_result();
    
    $success_message = "User approved successfully.";
}

// SQL query that selects all users not yet approved by the admin.
$sql = 
" SELECT
        user_id,
        username,
        lab,
        firstname,
        lastname,
        email

    FROM users
    
    WHERE approved = 0
    
    ORDER BY lastname";

$result = $link->query($sql);
?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Approve Users</title>

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

    <h2>Approve Users</h2>
    

    <h5 style="color:darkgreen">

        <?php
        if ($_GET['user_id']) {

            echo $success_message;

        } else {

            echo "<br>";
        }
        ?>

    </h5>

        

    <!-- Inventory table -->
    <table class="table">

        <tr>

            <th>First name</th>

            <th>Last name</th>

            <th>Lab group</th>

            <th>Username</th>

            <th>Email</th>

        </tr>

        <?php

        // Check whether any users exist
        if ($result->num_rows > 0) {

            // Display one row for each reagent lot
            while (
                $row =
                $result->fetch_assoc()
            ) {

                echo "<tr>";

                // First name
                echo "<td>" .

                htmlspecialchars(
                    $row["firstname"]
                ) .

                "</td>";

                // Last name
                echo "<td>" .

                htmlspecialchars(
                    $row["lastname"]
                ) .

                "</td>";

                // Lab group
                echo "<td>" .

                htmlspecialchars(
                    $row["lab"] ?? ""
                ) .

                "</td>";

                // Username
                echo "<td>" .

                htmlspecialchars(
                    $row["username"] ?? ""
                ) .

                "</td>";

                // Email address
                echo "<td>" .

                htmlspecialchars(
                    $row["email"] ?? ""
                ) . 

                "</td>";

                // Approve user button
                echo "<td>";

                echo
                "<a
                    class='button'
                    href='approve_users.php?user_id=" .
                    $row["user_id"] .
                    "'>
                    Approve user
                    </a>";
                
                echo "</td>";

                echo "</tr>";



            }

        } else {

            // Display this if there are no users in the database yet.

            echo
            "<tr>

                <td colspan='11'>
                    No new registered users.
                </td>

            </tr>";

        }
        ?>

    </table>


</main>

</body>

</html>

<?php

// Close database connection
$link->close();

?>