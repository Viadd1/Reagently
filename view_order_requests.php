<?php

/*
Allows administrators to view reagent order requests submitted by users.
*/


// Start session
session_start();

// User must be logged in
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



// Get order requests
// Get all submitted order requests. JOIN users is used so that the admin can see who submitted each request.

$sql =
"SELECT

    order_requests.request_id,
    order_requests.reagent_name,
    order_requests.request_text,
    order_requests.created_at,

    users.username,
    users.firstname,
    users.lastname,
    users.lab

FROM order_requests JOIN users ON order_requests.user_id = users.user_id
ORDER BY order_requests.created_at DESC";

// Execute query
$result = $link->query($sql);

?>


<!DOCTYPE html>

<html>

<head>

    <title>
        Reagently - Order Requests
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


    <h2>Requests of Orders</h2>


    <p>
        Reagent order requests submitted by users.
    </p>


    <br>



    <!-- Order request table -->

    <table border="1">


        <tr>

            <th>User</th>

            <th>Name</th>

            <th>Lab Group</th>

            <th>Reagent</th>

            <th>Request</th>

            <th>Date</th>

        </tr>



        <?php


        // Check whether any order requests exist
        if ($result->num_rows > 0) {


            // Display one row for each request
            while (
                $row =
                $result->fetch_assoc()
            ) {


                echo "<tr>";


                // Username
                echo "<td>" .

                htmlspecialchars(
                    $row["username"]
                ) .

                "</td>";


                // Full name
                echo "<td>" .

                htmlspecialchars(
                    ($row["firstname"] ?? "") .
                    " " .
                    ($row["lastname"] ?? "")
                ) .

                "</td>";


                // Lab group
                echo "<td>" .

                htmlspecialchars(
                    $row["lab"] ?? ""
                ) .

                "</td>";


                // Requested reagent
                echo "<td>" .

                htmlspecialchars(
                    $row["reagent_name"]
                ) .

                "</td>";


                // Request message
                echo "<td>" .

                htmlspecialchars(
                    $row["request_text"]
                ) .

                "</td>";


                // Date submitted
                echo "<td>" .

                htmlspecialchars(
                    $row["created_at"]
                ) .

                "</td>";


                echo "</tr>";

            }


        } else {


            // No order requests have been submitted yet
            echo
            "<tr>

                <td colspan='6'>
                    No order requests found.
                </td>

            </tr>";

        }


        ?>


    </table>


    <br><br>


    <a
        class="button"
        href="admin.php"
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