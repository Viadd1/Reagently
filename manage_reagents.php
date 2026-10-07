<?php
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

include 'db.php';

// Reagents with their nicknames (deleted reagents are hidden)
$reagents = $link->query(
    "SELECT r.reagent_id, r.name, r.catalog_number, r.category, r.unit,
            r.storage_condition,
            GROUP_CONCAT(n.nickname SEPARATOR ', ') AS nicknames
     FROM reagents r
     LEFT JOIN reagent_nicknames n ON n.reagent_id = r.reagent_id
     WHERE r.is_deleted = 0
     GROUP BY r.reagent_id
     ORDER BY r.name"
);

// Lots with reserved quantity, for the quantity-change section
$lots = $link->query(
    "SELECT l.lot_id, l.lot_number, l.current_quantity, r.name, r.unit,
            COALESCE((SELECT SUM(quantity_reserved) FROM reservations
                      WHERE lot_id = l.lot_id AND status = 'ACTIVE'), 0) AS reserved
     FROM reagent_lots l
     JOIN reagents r ON r.reagent_id = l.reagent_id
     WHERE r.is_deleted = 0
     ORDER BY r.name, l.lot_number"
);
?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Manage reagents</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>


<?php
// Main header and navigation bar
include "navbar.php";
?>

<main class="page">
    <h2>Manage Reagents</h2>

    <?php if (isset($_GET['msg'])): ?>
        <p><strong><?= htmlspecialchars($_GET['msg']) ?></strong></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;"><strong><?= htmlspecialchars($_GET['error']) ?></strong></p>
    <?php endif; ?>

    <table border="1">
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
                <form action="add_nickname.php" method="POST">
                    <input type="hidden" name="reagent_id" value="<?= (int)$row['reagent_id'] ?>">
                    <input type="text" name="nickname" maxlength="100" required>
                    <input class="button" type="submit" value="Add">
                </form>
            </td>
            <td>
                <form action="edit_reagent.php" method="POST">
                    <input type="hidden" name="reagent_id" value="<?= (int)$row['reagent_id'] ?>">
                    <input class="button" type="submit" value="Edit">
                </form>
                <form action="delete_reagent.php" method="POST"
                      onsubmit="return confirm('Delete this reagent?');">
                    <input type="hidden" name="reagent_id" value="<?= (int)$row['reagent_id'] ?>">
                    <input class="button" type="submit" value="Delete">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h3>Change Lot Quantity</h3>
    <table border="1">
        <tr>
            <th>Reagent</th><th>Lot</th><th>Physical</th><th>Reserved</th>
            <th>New quantity</th><th>Note</th><th></th>
        </tr>
        <?php while ($lot = $lots->fetch_assoc()): ?>
        <tr>
            <form action="update_quantity.php" method="POST">
                <td><?= htmlspecialchars($lot['name']) ?></td>
                <td><?= htmlspecialchars($lot['lot_number'] ?? '') ?></td>
                <td><?= htmlspecialchars($lot['current_quantity'] . ' ' . $lot['unit']) ?></td>
                <td><?= htmlspecialchars($lot['reserved'] . ' ' . $lot['unit']) ?></td>
                <td>
                    <input type="hidden" name="lot_id" value="<?= (int)$lot['lot_id'] ?>">
                    <input type="number" name="new_quantity" step="0.01" min="0" required>
                </td>
                <td><input type="text" name="note" placeholder="Reason"></td>
                <td><input class="button" type="submit" value="Update"></td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</main>
</body>
</html>
<?php $link->close(); ?>