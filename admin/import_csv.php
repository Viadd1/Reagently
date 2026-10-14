<?php
/*
Admin page for bulk importing reagents and reagent lots from CSV files.
The uploaded file is processed by DB_connections/insert_import.php.
*/

require_once __DIR__ . "/../init.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Access denied. Admin permission is required.");
}
?>
<!DOCTYPE html>
<html>
<head>
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
    <form action="/DB_connections/insert_import.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="import_type" value="reagents">
        <input type="file" name="csv_file" accept=".csv" required>
        <input class="button" type="submit" value="Import reagents">
    </form>

    <h3>2. Import lots</h3>
    <p>
        The reagent must already exist. Locations that do not exist yet
        are created automatically.
    </p>
    <form action="/DB_connections/insert_import.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="import_type" value="lots">
        <input type="file" name="csv_file" accept=".csv" required>
        <input class="button" type="submit" value="Import lots">
    </form>

    <a href="/admin.php">Back to admin page</a>
</main>
</body>
</html>