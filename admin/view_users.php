<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_csrf_token();
$searchQuery = $_GET['search_query'] ?? '';
if (!is_string($searchQuery)) $searchQuery = '';
$like = '%' . substr(trim($searchQuery), 0, 100) . '%';
$stmt = $link->prepare(
    "SELECT user_id, username, lab, firstname, lastname, email, approved
     FROM users WHERE company_id = ? AND role IN ('admin','user')
       AND (username LIKE ? OR firstname LIKE ? OR lastname LIKE ?)
     ORDER BY lastname, username"
);
$stmt->bind_param('isss', $companyId, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Administer Users</title>
    <!-- Use the existing stylesheet -->
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

    <h2>View Users</h2>
    <p>
        Search and administer users.
    </p>
    
    <br>

    <!-- Search bar and filters -->
    <search>
        <form 
        action="view_users.php"
        method="GET"
        >
            <input
            type="text"
            name="search_query"
            placeholder="Search name or username...">

            <input
            type="submit"
            value="Submit"
            class="button"
            >
        </form
        >
    </search>

    <!-- Inventory table -->
    <table class="table">

        <tr>

            <th>First name</th>

            <th>Last name</th>

            <th>Lab group</th>

            <th>Username</th>

            <th>Email</th>

            <th>Approved</th>

            <th>Edit</th>

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

                // Approved or not
                echo "<td>";

                if ($row["approved"] == 0) {

                    echo "No";
                } 

                else {
                    echo "Yes";
                }

                echo "</td>";

                // Button for editing users
                echo "<td>" .

                "<form action=" . BASE_URL . "db_connections/edit_user.php
                    method='POST'
                    style='display:inline;'
                    >
                    <input type='hidden' name='csrf_token' value='" . htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') . "'>
                    <input 
                    type='hidden' 
                    name='user_id' 
                    value='" . 
                    htmlspecialchars($row['user_id']) . 
                    "'>
                    <input class='button'
                    type='submit'
                    value='Edit user'>
                </form>" .
                
                "</td>";

                echo "</tr>";
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

<a
    class="button"
    href="<?= BASE_URL ?>admin.php"
>
    Back to Admin
</a>

</main>
</body>
</html>

<?php
// Close database connection
$link->close();
?>