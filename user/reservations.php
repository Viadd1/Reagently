<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// CSRF protection for cancelling reservations
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection
include BASE_PATH . '/db.php';
// Company Data Isolation
require_once BASE_PATH . '/includes/tenant_auth.php';
// Get current user's company
$companyId = tenant_require_user($link);
// Get current user ID
$user_id = (int) $_SESSION['user_id'];

//Get visible reservations
// Retrieve reservations from projects that the current user belongs to.
// The user can see reservations made by other users, but only if those reservations belong to a project that the current user is also a member of.
$sql =
"SELECT

    reservations.reservation_id,
    reservations.user_id,
    reservations.quantity_reserved,
    reservations.needed_date,
    reservations.status,
    reservations.created_at,

    projects.project_name,

    users.username,
    users.firstname,
    users.lastname,

    reagents.name AS reagent_name,
    reagents.catalog_number,
    reagents.unit,

    reagent_lots.lot_number,
    reagent_lots.supplier

FROM reservations

-- Connect each reservation to its project
JOIN projects ON reservations.project_id = projects.project_id

-- Check whether the currently logged-in user belongs to the reservation's project
JOIN project_members ON reservations.project_id = project_members.project_id

-- Get information about the user who originally made the reservation
JOIN users ON reservations.user_id = users.user_id

-- Get information about the selected lot
JOIN reagent_lots ON reservations.lot_id = reagent_lots.lot_id

-- Get reagent information
JOIN reagents ON reagent_lots.reagent_id = reagents.reagent_id

-- Check the reagent lot's storage company
JOIN locations
ON reagent_lots.location_id = locations.location_id

-- Only show reservations visible to this user
WHERE project_members.user_id = ?

-- The project belongs to the current company
AND projects.company_id = ?

-- The reservation creator belongs to the same company
AND users.company_id = ?

-- The reagent belongs to the current company
AND reagents.company_id = ?

-- The storage location belongs to the current company
AND locations.company_id = ?

ORDER BY

    CASE
        WHEN reservations.status = 'ACTIVE'
        THEN 0
        ELSE 1
    END,

    reservations.needed_date,
    reservations.created_at DESC";

// Prepare SQL query
$stmt = $link->prepare($sql);

// Bind current user's ID
$stmt->bind_param("iiiii", $user_id, $companyId, $companyId, $companyId, $companyId);

// Execute query
$stmt->execute();

// Get query result
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Reservations</title>
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

<!-- Main heading -->
<main class="page">
    <h2>Reservations</h2>
    <h3>Reservations from your projects are shown below!</h3>

<!-- Reservation table -->
<table class="table">

<tr>

    <th>Project</th>

    <th>Reagent</th>

    <th>Catalog No.</th>

    <th>Supplier</th>

    <th>Lot</th>

    <th>Reserved By</th>

    <th>Quantity</th>

    <th>Needed Date</th>

    <th>Status</th>

    <th>Created</th>

    <th>Action</th>

</tr>

<?php

// Check whether any visible reservations exist
if ($result->num_rows > 0) {

    // Loop through all visible reservations
    while (
        $row =
        $result->fetch_assoc()
    ) {

        echo "<tr>";

        // Project name
        echo "<td>" .
        htmlspecialchars(
            $row["project_name"]
        ) .
        "</td>";

        // Reagent name
        echo "<td>" .
        htmlspecialchars(
            $row["reagent_name"]
        ) .
        "</td>";

        // Catalog number
        echo "<td>" .
        htmlspecialchars(
            $row["catalog_number"]
        ) .
        "</td>";

        // Supplier
        echo "<td>" .
        htmlspecialchars(
            $row["supplier"] ?? ""
        ) .
        "</td>";

        // Lot number
        echo "<td>" .
        htmlspecialchars(
            $row["lot_number"] ?? ""
        ) .
        "</td>";

        // User who made the reservation
        $full_name =
            trim(($row["firstname"] ?? "") . " " . ($row["lastname"] ?? ""));

        $display_name = $row["username"];
        
        // Add first name and last name if they are available
        if ($full_name !== "") { $display_name .= " (" . $full_name . ")";}
        
        echo "<td>" .
        htmlspecialchars($display_name) .
        "</td>";

        // Reserved quantity + reagent unit
        echo "<td>" .
        htmlspecialchars(
            $row["quantity_reserved"]
        ) .
        " " .
        htmlspecialchars(
            $row["unit"]
        ) .
        "</td>";

        // Date when reagent is needed
        echo "<td>" .
        htmlspecialchars(
            $row["needed_date"]
        ) .
        "</td>";

        // Reservation status
        echo "<td>" .
        htmlspecialchars(
            $row["status"]
        ) .
        "</td>";

        // Reservation creation time
        echo "<td>" .
        htmlspecialchars(
            $row["created_at"]
        ) .
        "</td>";

        //Reservation actions
        echo "<td>";

        // For now, only the user who created the reservation can record usage or cancel their own ACTIVE reservation.
        // Other project members can see the reservation information but cannot change it.
        if (
            $row["status"] === "ACTIVE"
            &&
            (int) $row["user_id"] ===
            (int) $user_id
        ) {

            // Link to record the final usage
            echo
            "<a class='button' href='record_usage.php?reservation_id=" .
            $row["reservation_id"] .
            "'>
                Record usage
            </a>";

            echo "<br><br>";

            // Cancellation changes data: use POST and a CSRF token, not a GET link.
            echo "<form method='POST' action='" .
                htmlspecialchars(BASE_URL . "db_connections/cancel_reservation.php", ENT_QUOTES) .
                "' onsubmit=\"return confirm('Cancel this reservation?');\">" .
                "<input type='hidden' name='reservation_id' value='" . (int)$row['reservation_id'] . "'>" .
                "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) . "'>" .
                "<button class='button' type='submit'>Cancel</button>" .
                "</form>";

        } else {

            // No actions are available for:
            // - COMPLETED reservations
            // - CANCELLED reservations
            // - reservations created by another user

            echo "-";
        }

        echo "</td>";

        echo "</tr>";
    }

} else {

    // No reservations are visible to this user
    echo
    "<tr>

        <td colspan='11'>
            No reservations found.
        </td>

    </tr>";
}
?>
</table>

<!-- Create a new reservation -->
<a href="make_reservation.php" class="button">
Make a reservation
</a>

<br>

<a
    class="button"
    href="<?= BASE_URL ?>user.php"
>
    Back to User
</a>

</body>
</html>

<?php
// Close prepared statement
$stmt->close();
// Close database connection
$link->close();
?>