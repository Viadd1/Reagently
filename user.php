

<?php

// Start session and set base path variable
require_once "init.php";

// If the user is not logged in redirect to login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// Include database connection
include 'db.php';

// Company Data Isolation
require_once BASE_PATH . '/includes/tenant_auth.php';

// Verify user and company
$companyId = tenant_require_user($link);

// Get current user's ID
$user_id = (int) $_SESSION['user_id'];

// CSRF protection for cancelling reservations
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

 WHERE user_id = ? AND company_id = ?";

// Prepare query
$user_stmt =
    $link->prepare($user_sql);

// Bind current user ID
$user_stmt->bind_param(
    "ii",
    $user_id, $companyId
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

 WHERE project_members.user_id = ? AND projects.company_id = ?

 ORDER BY projects.project_name";

$project_stmt =
    $link->prepare($project_sql);

$project_stmt->bind_param(
    "ii",
    $user_id, $companyId
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

 JOIN projects ON reservations.project_id = projects.project_id

 JOIN reagent_lots ON reservations.lot_id = reagent_lots.lot_id

 JOIN reagents ON reagent_lots.reagent_id = reagents.reagent_id

 JOIN locations ON reagent_lots.location_id = locations.location_id

 WHERE reservations.user_id = ? AND projects.company_id = ? AND reagents.company_id = ? AND locations.company_id = ?

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
    "iiii",
    $user_id, $companyId, $companyId, $companyId,
);

$reservation_stmt->execute();

$reservation_result =
    $reservation_stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Reagently - User Page</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

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
            action=" . BASE_URL . "db_connections/edit_user.php
            method='POST'
            style='display:inline;'
            >
                <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . "'>
                <input 
                type='hidden' 
                name='user_id' 
                value='" . 
                htmlspecialchars($user_id) . 
                "'>
                <input
                class='button'
                type='submit'
                value='Edit user information'>
                 
            </form>";
    ?>
    </p>

    <br><br>

    <!-- My Projects -->
    <h2>My Projects</h2>

    <?php

    // Check whether the user belongs to any projects
    if ($project_result->num_rows > 0) {

        echo "<table class='table'>";

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

        echo "<table class='table'>";

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

            // Link to record the final usage
            echo
            "<a class='button' href=" . BASE_URL . "user/record_usage.php?reservation_id=" .
            $reservation["reservation_id"] .
            "'>
                Record usage
            </a>";

            echo "<br><br>";

            // Cancellation changes data: use POST and a CSRF token, not a GET link.
            echo "<form method='POST' action='" .
                htmlspecialchars(BASE_URL . "db_connections/cancel_reservation.php", ENT_QUOTES) .
                "' onsubmit=\"return confirm('Cancel this reservation?');\">" .
                "<input type='hidden' name='reservation_id' value='" . (int)$reservation['reservation_id'] . "'>" .
                "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) . "'>" .
                "<button class='button' type='submit'>Cancel</button>" .
                "</form>";

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
        href="<?= BASE_URL ?>user/reservations.php"
    >
        Project Reservations
    </a>

    <!-- View user's Usage history -->
    <a
        class="button"
        href="<?= BASE_URL ?>user/transactions.php"
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
