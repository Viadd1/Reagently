<?php
/**
 * Insert a reagent lot.
 * A lot has no company_id of its own: both its reagent and its location
 * must belong to the authenticated Company Admin's company.
 */

require_once __DIR__ . '/../init.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'authentication/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

// This checks the database-verified account, company status, and Admin role.
$companyId = tenant_require_admin($link);

// Verify that the request came from the Add Lot form.
$expectedToken = $_SESSION['csrf_token'] ?? null;
$submittedToken = $_POST['csrf_token'] ?? null;
if (
    !is_string($expectedToken) ||
    $expectedToken === '' ||
    !is_string($submittedToken) ||
    !hash_equals($expectedToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

function validOptionalLotDate(string $value): bool
{
    if ($value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

$reagentId = filter_var(
    $_POST['reagent_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$locationId = filter_var(
    $_POST['location_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$supplier = $_POST['supplier'] ?? '';
$lotNumber = $_POST['lot_number'] ?? '';
$quantityInput = $_POST['current_quantity'] ?? '';
$receivedDate = $_POST['received_date'] ?? '';
$expiryDate = $_POST['expiry_date'] ?? '';

$created = false;
$message = '';
$statusCode = 200;

// Reject missing IDs, unexpected array input, invalid quantities, and dates.
if (!is_int($reagentId) || !is_int($locationId)) {
    $message = 'Please select a valid reagent and location.';
    $statusCode = 400;
} elseif (
    !is_string($supplier) || !is_string($lotNumber) ||
    !is_string($quantityInput) || !is_string($receivedDate) ||
    !is_string($expiryDate)
) {
    $message = 'Invalid form data.';
    $statusCode = 400;
} else {
    $supplier = trim($supplier);
    $lotNumber = trim($lotNumber);
    $quantityInput = trim($quantityInput);
    $receivedDate = trim($receivedDate);
    $expiryDate = trim($expiryDate);

    // reagent_lots.current_quantity is DECIMAL(10,2): 0 to 99999999.99.
    if (!preg_match('/\A(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?\z/', $quantityInput)) {
        $message = 'Quantity must be a non-negative number with at most two decimal places.';
        $statusCode = 400;
    } elseif (!validOptionalLotDate($receivedDate) || !validOptionalLotDate($expiryDate)) {
        $message = 'Please provide valid dates in YYYY-MM-DD format.';
        $statusCode = 400;
    } else {
        // Keep DECIMAL accuracy without converting the submitted value to float.
        $quantityParts = explode('.', $quantityInput, 2);
        $quantity = $quantityParts[0] . '.' . str_pad($quantityParts[1] ?? '', 2, '0');
        $receivedDate = $receivedDate === '' ? null : $receivedDate;
        $expiryDate = $expiryDate === '' ? null : $expiryDate;

        try {
            // Atomic company-scoped insert. A forged reagent_id or location_id
            // from another company yields zero inserted rows.
            $sql = '
                INSERT INTO reagent_lots
                    (reagent_id, location_id, supplier, lot_number,
                     current_quantity, received_date, expiry_date)
                SELECT
                    r.reagent_id, loc.location_id, ?, ?, ?, ?, ?
                FROM reagents AS r
                JOIN locations AS loc ON loc.location_id = ?
                WHERE r.reagent_id = ?
                  AND r.company_id = ?
                  AND loc.company_id = ?
                  AND r.is_deleted = 0
            ';

            $stmt = $link->prepare($sql);
            $stmt->bind_param(
                'sssssiiii',
                $supplier,
                $lotNumber,
                $quantity,
                $receivedDate,
                $expiryDate,
                $locationId,
                $reagentId,
                $companyId,
                $companyId
            );
            $stmt->execute();
            $created = $stmt->affected_rows === 1;
            $stmt->close();

            if ($created) {
                $message = 'New reagent lot created successfully.';
            } else {
                $message = 'Selected reagent or location is not available in your company.';
                $statusCode = 404;
            }
        } catch (mysqli_sql_exception $e) {
            error_log('Reagently lot insert failed: ' . $e->getMessage());
            $message = 'Database error while adding the reagent lot.';
            $statusCode = 500;
        }
    }
}

$link->close();
http_response_code($statusCode);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Add Reagent Lot</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <br>
    <a class="button" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>admin/add_lot.php">
        <?= $created ? 'Add another lot' : 'Back to Add Reagent Lot' ?>
    </a>
    <a class="button" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>admin/manage_reagents.php">
        Manage Reagents
    </a>
</main>
</body>
</html>
