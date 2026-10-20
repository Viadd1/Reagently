<?php
/*
Admin page for bulk importing reagents and reagent lots from CSV files.
The uploaded file is processed by DB_connections/insert_import.php.
*/

require_once __DIR__ . "/../init.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /authentication/login.php");
    exit;
}

require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_csrf_token();
$link->close();
?>
<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Import CSV</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<?php include BASE_PATH . "/navbar.php"; ?>

<main class="page">
    <h2>Import from CSV</h2>
    <p>
        Import in this order: first the reagents, then the lots.
        All rows are checked first. If any row has a problem,
        nothing is imported and the problems are listed.
    </p>

    <h3>1. Import reagents</h3>
    <p>
        Required columns: <strong>name, catalog_number, category, unit, storage_condition</strong><br>
        Optional columns: <strong> nicknames</strong>
        (separate several nicknames with <strong>|</strong>)<br>
    </p>
    <form action="<?= BASE_URL ?>db_connections/insert_import.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="import_type" value="reagents">
        <input type="file" name="csv_file" accept=".csv" required>
        <input class="button" type="submit" value="Import reagents">
    </form>

    <h3>2. Import lots</h3>
    <p>
        The reagent must already exist. Locations that do not exist yet
        are created automatically.
    </p>
    <form action="<?= BASE_URL ?>db_connections/insert_import.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tenant_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="import_type" value="lots">
        <input type="file" name="csv_file" accept=".csv" required>
        <input class="button" type="submit" value="Import lots">
    </form>

    <a 
    href="/admin.php"
    class="button"
    >Back to admin page</a>
</main>
</body>
</html>