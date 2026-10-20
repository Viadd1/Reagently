<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// User must be logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// Include database connection
include BASE_PATH . '/db.php';

// Get information about the current user
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Every history row must belong to this company via user, reagent and location.
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_user($link);
$role = (string) $_SESSION['role'];
$sql = "SELECT it.transaction_id, it.transaction_type, it.quantity_change,
               it.note, it.created_at, u.username,
               r.name AS reagent_name, r.catalog_number, r.unit,
               l.lot_number, p.project_name
        FROM inventory_transactions it
        JOIN users u ON it.user_id = u.user_id
        JOIN reagent_lots l ON it.lot_id = l.lot_id
        JOIN reagents r ON l.reagent_id = r.reagent_id
        JOIN locations loc ON l.location_id = loc.location_id
        LEFT JOIN reservations res ON it.reservation_id = res.reservation_id
        LEFT JOIN projects p ON res.project_id = p.project_id
        WHERE u.company_id = ? AND r.company_id = ? AND loc.company_id = ?
          AND (p.project_id IS NULL OR p.company_id = ?)";
if ($role !== 'admin') { $sql .= ' AND it.user_id = ?'; }
$sql .= ' ORDER BY it.created_at DESC';
$stmt = $link->prepare($sql);
if ($role === 'admin') {
    $stmt->bind_param('iiii', $companyId, $companyId, $companyId, $companyId);
} else {
    $stmt->bind_param('iiiii', $companyId, $companyId, $companyId, $companyId, $user_id);
}

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

    <title>Reagently - Usage History</title>
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
    <h2>Reagent Inventory</h2>

<?php

// Explain what the current user can see
if ($role === 'admin') {
    echo
        "<p>
            Showing inventory transactions for your company.
        </p>";
} else {
    echo
        "<p>
            Showing your inventory transactions.
        </p>";
}
?>

<!-- Inventory transaction table -->
<table class="table">
    <tr>
        <th>Date</th>
        <th>Reagent</th>
        <th>Catalog No.</th>
        <th>Lot</th>
        <th>User</th>
        <th>Project</th>
        <th>Type</th>
        <th>Quantity Change</th>
        <th>Note</th>
    </tr>
<?php

// Check whether any transactions exist
if ($result->num_rows > 0) {

    // Display each transaction
    while (
        $row =
        $result->fetch_assoc()
    ) {

        echo "<tr>";

        // Transaction date and time
        echo "<td>" .
        htmlspecialchars(
            $row["created_at"]
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

        // Lot number
        echo "<td>" .
        htmlspecialchars(
            $row["lot_number"] ?? ""
        ) .
        "</td>";

        // User who caused the inventory change
        echo "<td>" .
        htmlspecialchars(
            $row["username"]
        ) .
        "</td>";

        // Project name
        // RECEIVE, DISCARD or ADJUST transactions may not have a reservation or project.
        echo "<td>" .
        htmlspecialchars(
            $row["project_name"] ?? "-"
        ) .
        "</td>";

        // Transaction type
        // Examples:
        // USAGE
        // RECEIVE
        // DISCARD
        // ADJUST
        echo "<td>" .
        htmlspecialchars(
            $row["transaction_type"]
        ) .
        "</td>";

        // Quantity change
        // Examples:
        // +100 mL
        // -6 mL
        echo "<td>";

        // Add a + sign for positive inventory changes
        if (
            $row["quantity_change"] > 0
        ) {
            echo "+";
        }

        echo
            htmlspecialchars(
                $row["quantity_change"]
            ) .
            " " .
            htmlspecialchars(
                $row["unit"]
            );

        echo "</td>";

        // Optional transaction note
        echo "<td>" .
        htmlspecialchars(
            $row["note"] ?? ""
        ) .
        "</td>";

        echo "</tr>";
    }

} else {

    // No transaction history exists
    echo
    "<tr>

        <td colspan='9'>
            No inventory transactions found.
        </td>

    </tr>";
}
?>
</table>

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