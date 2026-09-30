<?php

// Displays information for the currently logged-in user, including personal details, project memberships and reservations.

// Start session so that we know which user is currently logged in
session_start();

// If the user is not logged in redirect to login page
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

// Include database connection
include 'db.php';

// Get current user's ID from session
$user_id =
    $_SESSION['user_id'];

// Get user information:

// Get personal information about the currently logged-in user

$user_sql =
"SELECT

    username,
    firstname,
    lastname,
    lab,
    email,
    role

 FROM users

 WHERE user_id = ?";

// Prepare query
$user_stmt =
    $link->prepare($user_sql);

// Bind current user ID
$user_stmt->bind_param(
    "i",
    $user_id
);

// Execute query
$user_stmt->execute();

// Get query result
$user_result =
    $user_stmt->get_result();

// Check whether the user exists
if ($user_result->num_rows !== 1) {

    die(
        "User not found."
    );
}

// Fetch user information
$user =
    $user_result->fetch_assoc();

// Get user's projects:

// Get all projects that the current user belongs to.
// project_members connects users and projects.

$project_sql =
"SELECT

    projects.project_id,
    projects.project_name,
    projects.description

 FROM projects

 JOIN project_members

 ON projects.project_id
 =
 project_members.project_id

 WHERE project_members.user_id = ?

 ORDER BY projects.project_name";

$project_stmt =
    $link->prepare($project_sql);

$project_stmt->bind_param(
    "i",
    $user_id
);

$project_stmt->execute();

$project_result =
    $project_stmt->get_result();

// Get user's reservations

// Get reservations created by the currently logged-in user.
// This section shows only the user's own reservations.
// reservations.php can separately show reservations from all projects that the user belongs to.

$reservation_sql =
"SELECT

    reservations.reservation_id,
    reservations.quantity_reserved,
    reservations.needed_date,
    reservations.status,
    reservations.created_at,

    projects.project_name,

    reagents.name AS reagent_name,
    reagents.catalog_number,
    reagents.unit,

    reagent_lots.lot_number

 FROM reservations

 JOIN projects

 ON reservations.project_id
 =
 projects.project_id

 JOIN reagent_lots

 ON reservations.lot_id
 =
 reagent_lots.lot_id

 JOIN reagents

 ON reagent_lots.reagent_id
 =
 reagents.reagent_id

 WHERE reservations.user_id = ?

 ORDER BY

    CASE
        WHEN reservations.status = 'ACTIVE'
        THEN 0
        ELSE 1
    END,

    reservations.created_at DESC";

$reservation_stmt =
    $link->prepare($reservation_sql);

$reservation_stmt->bind_param(
    "i",
    $user_id
);

$reservation_stmt->execute();

$reservation_result =
    $reservation_stmt->get_result();

?>

<!DOCTYPE html>

<html>

<head>

    <title>
        Reagently - User
    </title>

    <!-- Use existing website design -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<!-- Website title -->
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

<!-- Main user page -->
<main class="page">

    <!-- User information -->
    <h2>User Information</h2>

    <p>

        <strong>Username:</strong>

        <?php

        echo htmlspecialchars(
            $user["username"]
        );

        ?>

    </p>

    <p>

        <strong>Name:</strong>

        <?php

        echo htmlspecialchars(
            ($user["firstname"] ?? "") .
            " " .
            ($user["lastname"] ?? "")
        );

        ?>

    </p>

    <p>

        <strong>Lab / Group:</strong>

        <?php

        echo htmlspecialchars(
            $user["lab"] ?? ""
        );

        ?>

    </p>

    <p>

        <strong>Email:</strong>

        <?php

        echo htmlspecialchars(
            $user["email"]
        );

        ?>

    </p>

    <p>

        <strong>Role:</strong>

        <?php

        echo htmlspecialchars(
            $user["role"]
        );

        echo "<form 
            action='/edit_user.php' 
            method='POST'
            style='display:inline;'
            >
                <input 
                type='hidden' 
                name='user_id' 
                value='" . 
                htmlspecialchars($user_id) . 
                "'>
                <button type='submit'
                class='button'>
                 Edit user information
                </button>
            </form>";
    ?>

    </p>

    <br><br>

    <!-- My Projects -->

    <h2>My Projects</h2>

    <?php

    // Check whether the user belongs to any projects

    if ($project_result->num_rows > 0) {

        echo "<table border='1'>";

        echo
        "<tr>

            <th>Project</th>

            <th>Description</th>

        </tr>";

        // Display each project
        while (
            $project =
            $project_result->fetch_assoc()
        ) {

            echo "<tr>";

            echo "<td>" .
            htmlspecialchars(
                $project["project_name"]
            ) .
            "</td>";

            echo "<td>" .
            htmlspecialchars(
                $project["description"] ?? ""
            ) .
            "</td>";

            echo "</tr>";

        }

        echo "</table>";

    } else {

        echo
        "<p>
            You are not currently a member
            of any projects.
        </p>";

    }

    ?>

    <br><br>

    <!-- My Reservations -->

    <h2>My Reservations</h2>

    <?php

    // Check whether the current user has created any reservations

    if ($reservation_result->num_rows > 0) {

        echo "<table border='1'>";

        echo
        "<tr>

            <th>Project</th>

            <th>Reagent</th>

            <th>Catalog No.</th>

            <th>Lot</th>

            <th>Quantity</th>

            <th>Needed Date</th>

            <th>Status</th>

            <th>Action</th>

        </tr>";

        // Display each reservation
        while (
            $reservation =
            $reservation_result->fetch_assoc()
        ) {

            echo "<tr>";

            // Project
            echo "<td>" .
            htmlspecialchars(
                $reservation["project_name"]
            ) .
            "</td>";

            // Reagent
            echo "<td>" .
            htmlspecialchars(
                $reservation["reagent_name"]
            ) .
            "</td>";

            // Catalog number
            echo "<td>" .
            htmlspecialchars(
                $reservation["catalog_number"]
            ) .
            "</td>";

            // Lot
            echo "<td>" .
            htmlspecialchars(
                $reservation["lot_number"] ?? ""
            ) .
            "</td>";

            // Reserved quantity
            echo "<td>" .
            htmlspecialchars(
                $reservation["quantity_reserved"]
            ) .
            " " .
            htmlspecialchars(
                $reservation["unit"]
            ) .
            "</td>";

            // Needed date
            echo "<td>" .
            htmlspecialchars(
                $reservation["needed_date"]
            ) .
            "</td>";

            // Status
            echo "<td>" .
            htmlspecialchars(
                $reservation["status"]
            ) .
            "</td>";

            // Action
            echo "<td>";

            // Only ACTIVE reservations
            // can still be used or cancelled
            if (
                $reservation["status"]
                ===
                "ACTIVE"
            ) {

                echo
                "<a href='record_usage.php?reservation_id=" .
                $reservation["reservation_id"] .
                "'>
                    Record usage
                </a>";

                echo "<br>";

                echo
                "<a href='cancel_reservation.php?reservation_id=" .
                $reservation["reservation_id"] .
                "'>
                    Cancel
                </a>";

            } else {

                echo "-";

            }

            echo "</td>";

            echo "</tr>";

        }

        echo "</table>";

    } else {

        echo
        "<p>
            You have no reservations.
        </p>";

    }

    ?>

    <br><br>

    <!-- View reservations from all projects that the user belongs to -->
    <a
        class="button"
        href="reservations.php"
    >
        Project Reservations
    </a>

    <!-- View user's Usage history -->
    <a
        class="button"
        href="transactions.php"
    >
        Usage History
    </a>

</main>

</body>

</html>

<?php

// Close SQL statements

$user_stmt->close();

$project_stmt->close();

$reservation_stmt->close();

// Close database connection

$link->close();

?>
