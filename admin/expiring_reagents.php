<?php
/*
Admin page listing reagent lots that are already expired or expire within
the next month. Only lots that are still in stock (quantity > 0) are shown.
*/

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Include database connection
include BASE_PATH . "/db.php";

// Only logged-in admins may open this page
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Access denied. Admin permission is required.");
}

$sql =
"SELECT
    r.name,
    r.catalog_number,
    r.unit,

    l.supplier,
    l.lot_number,
    l.current_quantity,
    l.expiry_date,
    DATEDIFF(l.expiry_date, CURDATE()) AS days_left,

    loc.room,
    loc.storage_unit,
    loc.shelf,
    loc.box_name

 FROM reagent_lots l

 JOIN reagents r
 ON r.reagent_id = l.reagent_id

 JOIN locations loc
 ON loc.location_id = l.location_id

 WHERE r.is_deleted = 0
 AND l.current_quantity > 0
 AND l.expiry_date IS NOT NULL
 AND l.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)

 ORDER BY l.expiry_date ASC";

$result = $link->query($sql);

// Split into already expired and expiring soon
$expired = [];
$soon    = [];
while ($row = $result->fetch_assoc()) {
    if ((int)$row['days_left'] < 0) {
        $expired[] = $row;
    } else {
        $soon[] = $row;
    }
}

function location_text($row) {
    $parts = array_filter(
        [$row['room'], $row['storage_unit'], $row['shelf'], $row['box_name']],
        fn($p) => $p !== null && $p !== ''
    );
    return implode(' / ', $parts);
}

function days_text($days) {
    $days = (int)$days;
    if ($days < 0)  return 'Expired ' . abs($days) . ' day(s) ago';
    if ($days === 0) return 'Expires today';
    return 'In ' . $days . ' day(s)';
}

function render_table($rows) {
    echo '<table border="1">';
    echo '<tr>
            <th>Reagent</th><th>Catalog No.</th><th>Supplier</th><th>Lot</th>
            <th>Quantity</th><th>Location</th><th>Expiry date</th><th>Status</th>
          </tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($r['name']) . '</td>';
        echo '<td>' . htmlspecialchars($r['catalog_number']) . '</td>';
        echo '<td>' . htmlspecialchars($r['supplier'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['lot_number'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['current_quantity'] . ' ' . $r['unit']) . '</td>';
        echo '<td>' . htmlspecialchars(location_text($r)) . '</td>';
        echo '<td>' . htmlspecialchars($r['expiry_date']) . '</td>';
        echo '<td>' . htmlspecialchars(days_text($r['days_left'])) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reagently - Expiring Reagents</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<?php include BASE_PATH . "/navbar.php"; ?>

<main class="page">
    <h2>Expiring Reagents</h2>
    <p>
        Lots that are already expired or expire within the next month
        and are still in stock.
    </p>

    <h3>Already expired (<?= count($expired) ?>)</h3>
    <?php if ($expired): render_table($expired); else: ?>
        <p>None.</p>
    <?php endif; ?>

    <h3>Expiring within 1 month (<?= count($soon) ?>)</h3>
    <?php if ($soon): render_table($soon); else: ?>
        <p>None.</p>
    <?php endif; ?>

    <p>
        After you discard an expired lot, set its quantity to 0 on
        <a href="/admin/manage_reagents.php">Manage Reagents</a>
        and it disappears from this list.
    </p>

    <a href="/admin.php">Back to admin page</a>
</main>
</body>
</html>
<?php $link->close(); ?>
