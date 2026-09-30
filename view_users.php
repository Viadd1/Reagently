<?php

/*
Administration page for Reagently users, where 
Only users with the admin role can access this page.
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

// Include database connection
include 'db.php';

// Give search_query a default value
// when the page is opened without a search

$_GET['search_query'] =
    $_GET['search_query'] ?? '';

// If admin has searched for something, save in variable
if ($_GET['search_query']) {
    $search_query = $_GET['search_query'];
} 
else {
    $search_query = "";
}


$sql = 
" SELECT
        user_id,
        username,
        lab,
        firstname,
        lastname,
        email

    FROM users
    
    WHERE username LIKE '%$search_query%'

    OR firstname LIKE '%$search_query%'

    OR lastname LIKE '%$search_query%'
    
    ORDER BY lastname";

$result = $link->query($sql);
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

    <h2>View Users</h2>

    <p>
        Search and administer users.
    </p>
    
    <br>

    <!-- Search bar and filters -->

    <search>
        <form 
        action="/view_users.php"
        method="GET"
        >

            <input
            type="text"
            name="search_query"
            placeholder="Search name or username...">

            <input
            type="submit"
            value="Submit">

        </form
        >
    </search>


    <!-- Inventory table -->
    <table border="1">

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

                // Button for editing users (uses POST method for safety)
                echo "<td>";

                echo "
                    <form 
                    action='/edit_user.php' 
                    method='POST'
                    style='display:inline;'
                    >
                        <input 
                        type='hidden' 
                        name='user_id' 
                        value='" . 
                        htmlspecialchars($row['user_id']) . 
                        "'>
                        <button type='submit'
                        class='button'>
                        Edit user
                        </button>
                    </form>";
                

                "</tr>";

            }

        } else {

            // Display this if there are no users in the database yet.

            echo
            "<tr>

                <td colspan='11'>
                    No users found.
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