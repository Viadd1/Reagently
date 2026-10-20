<?php
/**
 * Add Reagent Lot
 * Displays a form for Company Admins to add a lot of an existing reagent.
 * Only reagents and storage locations belonging to the current company
 * are available for selection.
 */

require_once __DIR__ . '/../init.php';

// Require login.
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

// Verify the current user and their active company.
$companyId = tenant_require_user($link);
$userId = (int) $_SESSION['user_id'];

// Verify Company Admin permission against the database.
$adminStmt = $link->prepare(
    "SELECT 1
     FROM users
     WHERE user_id = ?
       AND company_id = ?
       AND role = 'admin'
     LIMIT 1"
);
$adminStmt->bind_param('ii', $userId, $companyId);
$adminStmt->execute();
$isAdmin = $adminStmt->get_result()->num_rows === 1;
$adminStmt->close();

if (!$isAdmin) {
    http_response_code(403);
    exit('Company Admin permission required.');
}

// CSRF token for the Add Lot form.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Reagents must belong to this company and must not be soft-deleted.
$reagentStmt = $link->prepare(
    'SELECT reagent_id, name, catalog_number, unit
     FROM reagents
     WHERE company_id = ? AND is_deleted = 0
     ORDER BY name, catalog_number'
);
$reagentStmt->bind_param('i', $companyId);
$reagentStmt->execute();
$reagents = $reagentStmt->get_result();

// Storage locations must also belong to this company.
$locationStmt = $link->prepare(
    'SELECT location_id, room, storage_unit, shelf, box_name
     FROM locations
     WHERE company_id = ?
     ORDER BY room, storage_unit, shelf, box_name'
);
$locationStmt->bind_param('i', $companyId);
$locationStmt->execute();
$locations = $locationStmt->get_result();

$hasReagents = $reagents->num_rows > 0;
$hasLocations = $locations->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Add Reagent Lot</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>

<?php include BASE_PATH . '/navbar.php'; ?>

<main class="page">
    <h1>Add Reagent Lot</h1>

    <?php if (!$hasReagents): ?>
        <p>No reagents are available in your company. Please add a reagent first.</p>
    <?php endif; ?>

    <?php if (!$hasLocations): ?>
        <p>No storage locations are available in your company. Please add a location first.</p>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>db_connections/insert_lot.php" method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        Reagent:
        <select name="reagent_id" required>
            <option value="" disabled selected>Select a reagent</option>
            <?php while ($row = $reagents->fetch_assoc()): ?>
                <option value="<?= (int) $row['reagent_id'] ?>">
                    <?= htmlspecialchars(
                        $row['name'] . ' - ' . $row['catalog_number'] .
                        ' (' . $row['unit'] . ')',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <br><br>

        Supplier:
        <input type="text" name="supplier">

        <br><br>

        Lot number:
        <input type="text" name="lot_number">

        <br><br>

        Current quantity:
        <input
            type="number"
            name="current_quantity"
            step="0.01"
            min="0"
            required
        >

        <br><br>

        Location:
        <select name="location_id" required>
            <option value="" disabled selected>Select a location</option>
            <?php while ($row = $locations->fetch_assoc()): ?>
                <option value="<?= (int) $row['location_id'] ?>">
                    <?= htmlspecialchars(
                        ($row['room'] ?? '') . ' / ' .
                        ($row['storage_unit'] ?? '') . ' / ' .
                        ($row['shelf'] ?? '') . ' / ' .
                        ($row['box_name'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <br><br>

        Received date:
        <input type="date" name="received_date">

        <br><br>

        Expiry date:
        <input type="date" name="expiry_date">

        <br><br>

        <input
            class="button"
            type="submit"
            value="Add Reagent Lot"
            <?= (!$hasReagents || !$hasLocations) ? 'disabled' : '' ?>
        >
    </form>

    <br>

    <a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>
</main>
</body>
</html>

<?php
$reagentStmt->close();
$locationStmt->close();
$link->close();
?>
