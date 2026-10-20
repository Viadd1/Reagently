
<?php
// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

include BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

$companyId = tenant_require_user($link);
// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Company Admin only
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Company Admin permission required.');
}


 // Reagents with nicknames
$reagentSql = "SELECT r.reagent_id, r.name, r.catalog_number, r.category, r.unit, r.storage_condition,
    GROUP_CONCAT(n.nickname SEPARATOR ', ') AS nicknames

FROM reagents r

LEFT JOIN reagent_nicknames n ON n.reagent_id = r.reagent_id

WHERE r.is_deleted = 0 AND r.company_id = ?

GROUP BY r.reagent_id
ORDER BY r.name
";

$reagentStmt = $link->prepare($reagentSql);
$reagentStmt->bind_param("i", $companyId);
$reagentStmt->execute();

$reagents = $reagentStmt->get_result();


// Lots with reserved quantities
$lotSql = "SELECT l.lot_id, l.lot_number, l.current_quantity, r.name, r.unit,

    COALESCE(
        (
            SELECT SUM(res.quantity_reserved)
            FROM reservations res
            WHERE res.lot_id = l.lot_id
              AND res.status = 'ACTIVE'
        ),
        0
    ) AS reserved

FROM reagent_lots l

JOIN reagents r ON r.reagent_id = l.reagent_id

JOIN locations loc ON loc.location_id = l.location_id

WHERE r.is_deleted = 0 AND r.company_id = ? AND loc.company_id = ?

ORDER BY r.name, l.lot_number
";

$lotStmt = $link->prepare($lotSql);

$lotStmt->bind_param(
    "ii",
    $companyId,
    $companyId
);

$lotStmt->execute();

$lots = $lotStmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Manage reagents</title>
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
    <h2>Manage Reagents</h2>

    <?php if (isset($_GET['msg'])): ?>
        <p><strong><?= htmlspecialchars($_GET['msg']) ?></strong></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;"><strong><?= htmlspecialchars($_GET['error']) ?></strong></p>
    <?php endif; ?>

    <table class="table">
        <tr>
            <th>Reagent</th><th>Catalog No.</th><th>Category</th><th>Unit</th>
            <th>Storage</th><th>Nicknames</th><th>Add nickname</th><th>Actions</th>
        </tr>
        <?php while ($row = $reagents->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['catalog_number']) ?></td>
            <td><?= htmlspecialchars($row['category'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['unit']) ?></td>
            <td><?= htmlspecialchars($row['storage_condition'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['nicknames'] ?? '') ?></td>
            <td>
                <form action="<?= BASE_URL ?>db_connections/add_nickname.php" method="POST">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <input type="hidden" name="reagent_id" value="<?= (int)$row['reagent_id'] ?>">
                    <input type="text" name="nickname" maxlength="100" required>
                    <input class="button" type="submit" value="Add">
                </form>
            </td>




<td>
    <!-- Edit Reagent -->
    <form
        action="<?= BASE_URL ?>db_connections/edit_reagent.php"
        method="POST"
    >
        <input
            type="hidden"
            name="reagent_id"
            value="<?= (int)$row['reagent_id'] ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <input
            class="button"
            type="submit"
            value="Edit"
        >
    </form>

    <br>

    <!-- Delete Reagent -->
    <form
        action="<?= BASE_URL ?>db_connections/delete_reagent.php"
        method="POST"
        onsubmit="return confirm('Delete this reagent?');"
    >
        <input
            type="hidden"
            name="reagent_id"
            value="<?= (int)$row['reagent_id'] ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <input
            class="button"
            type="submit"
            value="Delete"
        >
    </form>
</td>



        </tr>
        <?php endwhile; ?>
    </table>

    <h3>Change Lot Quantity</h3>
    <table class="table">
        <tr>
            <th>Reagent</th><th>Lot</th><th>Physical</th><th>Reserved</th>
            <th>New quantity</th><th>Note</th><th></th>
        </tr>

<?php while ($lot = $lots->fetch_assoc()): ?>

<?php
    $lotId = (int) $lot['lot_id'];
    $formId = 'update-lot-' . $lotId;
?>

<tr>
    <td>
        <?= htmlspecialchars($lot['name']) ?>
    </td>

    <td>
        <?= htmlspecialchars($lot['lot_number'] ?? '') ?>
    </td>

    <td>
        <?= htmlspecialchars($lot['current_quantity'] . ' ' . $lot['unit']) ?>
    </td>

    <td>
        <?= htmlspecialchars($lot['reserved'] . ' ' . $lot['unit']) ?>
    </td>

    <td>
        <input
            type="number"
            name="new_quantity"
            form="<?= $formId ?>"
            step="0.01"
            min="0"
            required
        >
    </td>

    <td>
        <input
            type="text"
            name="note"
            form="<?= $formId ?>"
            placeholder="Reason"
        >
    </td>

    <td>
        <form
            id="<?= $formId ?>"
            action="<?= BASE_URL ?>db_connections/update_quantity.php"
            method="POST"
        >
            <input
                type="hidden"
                name="lot_id"
                value="<?= $lotId ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <button class="button" type="submit">
                Update
            </button>
        </form>
    </td>
</tr>

<?php endwhile; ?>

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
// Close prepared statements
$reagentStmt->close();
$lotStmt->close();

// Close database connection
$link->close();
?>