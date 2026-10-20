<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// User must be logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: /authentication/login.php");
    exit;
}

// The company is verified against the current session's database account.
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_user($link);
tenant_require_post_csrf();
$userId = (int)$_SESSION['user_id'];
$reagentIdRaw = $_POST['reagent_id'] ?? '';
$name = $_POST['reagent_name'] ?? null;
$note = $_POST['request_text'] ?? null;
if (!is_string($name) || !is_string($note) || trim($name) === '' || trim($note) === '' || strlen(trim($name)) > 255) {
    http_response_code(400); exit('Invalid reagent name or request.');
}
$name=trim($name); $note=trim($note);
try {
    if ($reagentIdRaw !== '') {
        $reagentId=filter_var($reagentIdRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!is_int($reagentId)) {http_response_code(400); exit('Invalid reagent ID.');}
        // The reagent must belong to the submitter's company.
        $stmt = $link->prepare(
            "INSERT INTO order_requests (user_id, reagent_id, reagent_name, request_text)
             SELECT ?, r.reagent_id, r.name, ? FROM reagents r
             WHERE r.reagent_id = ? AND r.company_id = ? AND r.is_deleted = 0"
        );
        $stmt->bind_param('isii', $userId, $note, $reagentId, $companyId);
    } else {
        $stmt = $link->prepare(
            'INSERT INTO order_requests (user_id, reagent_id, reagent_name, request_text) VALUES (?, NULL, ?, ?)'
        );
        $stmt->bind_param('iss', $userId, $name, $note);
    }
    $stmt->execute();
    $inserted = $stmt->affected_rows === 1;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log('Order insert failed: '.$e->getMessage());
    $inserted=false;
}
$link->close();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Request Order</title>
<link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head><body>
<?php include BASE_PATH . '/navbar.php'; ?><main class="page">
<h2><?= $inserted ? 'Order request submitted successfully.' : 'Order request could not be submitted.' ?></h2>
<a class="button" href="<?= BASE_URL ?>user/request_order.php">Back to request</a>
</main></body></html>
