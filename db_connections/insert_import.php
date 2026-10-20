<?php
/*
Handles CSV bulk imports (reagents or lots) uploaded from import_csv.php.
All rows are validated first. If any row has a problem, nothing is
imported. Otherwise everything is saved in one database transaction.
*/

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Include database connection
include BASE_PATH . "/db.php";
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_require_post_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

const MAX_ROWS  = 1000;
const MAX_BYTES = 1048576; // 1 MB

$admin_id = (int)$_SESSION['user_id'];
$errors   = [];
$summary  = [];


/* ---------- helpers ---------- */

// Read a CSV file. Accepts comma or semicolon (Excel) as separator.
function read_csv($path, &$err) {
    $handle = fopen($path, 'r');
    if (!$handle) { $err = 'Could not open the uploaded file.'; return null; }

    $first = fgets($handle);
    if ($first === false) { fclose($handle); $err = 'The file is empty.'; return null; }
    $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);   // remove BOM
    $delimiter = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
    rewind($handle);

    $header = null;
    $rows   = [];
    $line   = 0;
    while (($data = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
        $line++;
        if ($line === 1) {
            $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
            $header  = array_map(fn($h) => strtolower(trim($h)), $data);
            continue;
        }
        if (count($data) === 1 && trim($data[0]) === '') continue; // blank line
        $rows[] = ['line' => $line, 'data' => $data];
    }
    fclose($handle);
    return ['header' => $header, 'rows' => $rows];
}

// Turn one CSV row into [column name => value]
function row_assoc($header, $data) {
    $assoc = [];
    foreach ($header as $i => $name) {
        $assoc[$name] = trim($data[$i] ?? '');
    }
    return $assoc;
}

function valid_date($v) {
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return $d && $d->format('Y-m-d') === $v;
}


/* ---------- reagents ---------- */

function import_reagents($link, $csv, $admin_id, $companyId, &$errors, &$summary) {
    $cats = [
        'organic'     => 'Organic',
        'inorganic'   => 'Inorganic',
        'biochemical' => 'Biochemical',
        'other'       => 'Other',
    ];

    $exists = $link->prepare("SELECT 1 FROM reagents WHERE catalog_number = ? AND company_id = ?");
    $seen   = [];
    $valid  = [];

    foreach ($csv['rows'] as $row) {
        $line = $row['line'];
        $r    = row_assoc($csv['header'], $row['data']);
        $rowErrors = [];

        $name    = $r['name'] ?? '';
        $catalog = $r['catalog_number'] ?? '';
        $unit    = $r['unit'] ?? '';
        $storage = ($r['storage_condition'] ?? '') ?: null;
        $catKey  = strtolower($r['category'] ?? '');

        if ($name === '')    $rowErrors[] = 'name is empty';
        if ($catalog === '') $rowErrors[] = 'catalog_number is empty';
        if ($unit === '')    $rowErrors[] = 'unit is empty';
        if (!isset($cats[$catKey])) {
            $rowErrors[] = 'category must be Organic, Inorganic, Biochemical or Other';
        }
        if (strlen($name) > 255)    $rowErrors[] = 'name is too long (max 255)';
        if (strlen($catalog) > 100) $rowErrors[] = 'catalog_number is too long (max 100)';
        if (strlen($unit) > 20)     $rowErrors[] = 'unit is too long (max 20)';
        if ($storage !== null && strlen($storage) > 100) {
            $rowErrors[] = 'storage_condition is too long (max 100)';
        }

        if ($catalog !== '') {
            if (isset($seen[strtolower($catalog)])) {
                $rowErrors[] = "catalog_number $catalog appears twice in the file";
            } else {
                $exists->bind_param("si", $catalog, $companyId);
                $exists->execute();
                if ($exists->get_result()->num_rows > 0) {
                    $rowErrors[] = "a reagent with catalog_number $catalog already exists";
                }
            }
            $seen[strtolower($catalog)] = true;
        }

        // Nicknames, separated by |
        $nicknames = [];
        foreach (explode('|', $r['nicknames'] ?? '') as $n) {
            $n = trim($n);
            if ($n === '') continue;
            if (strlen($n) > 100) { $rowErrors[] = "nickname '$n' is too long (max 100)"; continue; }
            $nicknames[strtolower($n)] = $n;   // removes duplicates
        }

        if ($rowErrors) {
            foreach ($rowErrors as $e) { $errors[] = "Line $line: $e."; }
            continue;
        }

        $valid[] = [
            'name' => $name, 'catalog' => $catalog, 'category' => $cats[$catKey],
            'unit' => $unit, 'storage' => $storage, 'nicknames' => array_values($nicknames),
        ];
    }
    $exists->close();

    if ($errors) return;

    $link->begin_transaction();
    try {
        $ins = $link->prepare(
            "INSERT INTO reagents (company_id, name, catalog_number, category, unit, storage_condition)
             VALUES (?, ?, ?, ?, ?, ?)");
        $nickIns = $link->prepare(
            "INSERT INTO reagent_nicknames (reagent_id, nickname, created_by) VALUES (?, ?, ?)");

        $nickCount = 0;
        foreach ($valid as $v) {
            $ins->bind_param("isssss", $companyId, $v['name'], $v['catalog'], $v['category'], $v['unit'], $v['storage']);
            $ins->execute();
            $reagent_id = $link->insert_id;

            foreach ($v['nicknames'] as $n) {
                $nickIns->bind_param("isi", $reagent_id, $n, $admin_id);
                $nickIns->execute();
                $nickCount++;
            }
        }
        $link->commit();
        $summary[] = count($valid) . " reagent(s) imported.";
        $summary[] = "$nickCount nickname(s) added.";
    } catch (mysqli_sql_exception $e) {
        $link->rollback();
        error_log('CSV import failed: ' . $e->getMessage());
        $errors[] = 'Database error; nothing was imported.';
    }
}


/* ---------- lots ---------- */

function import_lots($link, $csv, $companyId, &$errors, &$summary) {
    $findReagent = $link->prepare(
        "SELECT reagent_id FROM reagents WHERE catalog_number = ? AND company_id = ? AND is_deleted = 0");
    $valid = [];

    foreach ($csv['rows'] as $row) {
        $line = $row['line'];
        $r    = row_assoc($csv['header'], $row['data']);
        $rowErrors = [];

        $catalog  = $r['catalog_number'] ?? '';
        $supplier = ($r['supplier'] ?? '') ?: null;
        $lot_no   = ($r['lot_number'] ?? '') ?: null;
        $qtyText  = str_replace(',', '.', $r['quantity'] ?? '');
        $room     = $r['room'] ?? '';
        $unit_loc = ($r['storage_unit'] ?? '') ?: null;
        $shelf    = ($r['shelf'] ?? '') ?: null;
        $box      = ($r['box_name'] ?? '') ?: null;
        $received = ($r['received_date'] ?? '') ?: null;
        $expiry   = ($r['expiry_date'] ?? '') ?: null;

        // Reagent must exist and not be deleted
        $reagent_id = null;
        if ($catalog === '') {
            $rowErrors[] = 'catalog_number is empty';
        } else {
            $findReagent->bind_param("si", $catalog, $companyId);
            $findReagent->execute();
            $found = $findReagent->get_result()->fetch_assoc();
            if (!$found) {
                $rowErrors[] = "no reagent with catalog_number $catalog (import or add the reagent first)";
            } else {
                $reagent_id = (int)$found['reagent_id'];
            }
        }

        if (!is_numeric($qtyText) || (float)$qtyText < 0 || (float)$qtyText > 99999999.99) {
            $rowErrors[] = 'quantity must be a number from 0 to 99999999.99';
        }
        if ($room === '') $rowErrors[] = 'room is empty';

        foreach ([['room', $room, 100], ['storage_unit', $unit_loc, 100], ['shelf', $shelf, 100],
                  ['box_name', $box, 100], ['supplier', $supplier, 255], ['lot_number', $lot_no, 100]] as [$col, $val, $max]) {
            if ($val !== null && strlen($val) > $max) $rowErrors[] = "$col is too long (max $max)";
        }

        if ($received !== null && !valid_date($received)) $rowErrors[] = 'received_date must be YYYY-MM-DD';
        if ($expiry   !== null && !valid_date($expiry))   $rowErrors[] = 'expiry_date must be YYYY-MM-DD';

        if ($rowErrors) {
            foreach ($rowErrors as $e) { $errors[] = "Line $line: $e."; }
            continue;
        }

        $valid[] = [
            'reagent_id' => $reagent_id, 'supplier' => $supplier, 'lot_number' => $lot_no,
            'quantity' => (float)$qtyText, 'room' => $room, 'storage_unit' => $unit_loc,
            'shelf' => $shelf, 'box' => $box, 'received' => $received, 'expiry' => $expiry,
        ];
    }
    $findReagent->close();

    if ($errors) return;

    $link->begin_transaction();
    try {
        $findLoc = $link->prepare(
            "SELECT location_id FROM locations
             WHERE room = ? AND storage_unit <=> ? AND shelf <=> ? AND box_name <=> ? AND company_id = ?");
        $newLoc = $link->prepare(
            "INSERT INTO locations (company_id, room, storage_unit, shelf, box_name) VALUES (?, ?, ?, ?, ?)");
        $newLot = $link->prepare(
            "INSERT INTO reagent_lots
             (reagent_id, location_id, supplier, lot_number, current_quantity, received_date, expiry_date)
             VALUES (?, ?, ?, ?, ?, ?, ?)");

        $locCache = [];
        $locCreated = 0;

        foreach ($valid as $v) {
            $key = implode('|', [$v['room'], $v['storage_unit'], $v['shelf'], $v['box']]);

            if (!isset($locCache[$key])) {
                $findLoc->bind_param("ssssi", $v['room'], $v['storage_unit'], $v['shelf'], $v['box'], $companyId);
                $findLoc->execute();
                $loc = $findLoc->get_result()->fetch_assoc();

                if ($loc) {
                    $locCache[$key] = (int)$loc['location_id'];
                } else {
                    $newLoc->bind_param("issss", $companyId, $v['room'], $v['storage_unit'], $v['shelf'], $v['box']);
                    $newLoc->execute();
                    $locCache[$key] = $link->insert_id;
                    $locCreated++;
                }
            }

            $location_id = $locCache[$key];
            $newLot->bind_param("iissdss", $v['reagent_id'], $location_id, $v['supplier'],
                                $v['lot_number'], $v['quantity'], $v['received'], $v['expiry']);
            $newLot->execute();
        }
        $link->commit();
        $summary[] = count($valid) . " lot(s) imported.";
        $summary[] = "$locCreated new location(s) created.";
    } catch (mysqli_sql_exception $e) {
        $link->rollback();
        error_log('CSV import failed: ' . $e->getMessage());
        $errors[] = 'Database error; nothing was imported.';
    }
}


/* ---------- main ---------- */

function run_import($link, $admin_id, $companyId, &$errors, &$summary) {
    $type = $_POST['import_type'] ?? '';
    if (!in_array($type, ['reagents', 'lots'], true)) {
        $errors[] = 'Invalid import type.'; return;
    }

    $f = $_FILES['csv_file'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'No file was uploaded, or the upload failed.'; return;
    }
    if ($f['size'] > MAX_BYTES) {
        $errors[] = 'The file is too large (max 1 MB).'; return;
    }
    if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $errors[] = 'Please upload a .csv file.'; return;
    }

    $csv = read_csv($f['tmp_name'], $err);
    if ($csv === null) { $errors[] = $err; return; }

    $required = ($type === 'reagents')
        ? ['name', 'catalog_number', 'category', 'unit']
        : ['catalog_number', 'quantity', 'room'];
    $missing = array_diff($required, $csv['header']);
    if ($missing) {
        $errors[] = 'Missing column(s) in the header row: ' . implode(', ', $missing) . '.'; return;
    }
    if (count($csv['rows']) === 0) { $errors[] = 'The file has no data rows.'; return; }
    if (count($csv['rows']) > MAX_ROWS) {
        $errors[] = 'Too many rows (max ' . MAX_ROWS . ').'; return;
    }

    if ($type === 'reagents') {
        import_reagents($link, $csv, $admin_id, $companyId, $errors, $summary);
    } else {
        import_lots($link, $csv, $companyId, $errors, $summary);
    }
}

run_import($link, $admin_id, $companyId, $errors, $summary);
?>
<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Import result</title>
    <link rel="stylesheet" href="/style.css">
    
</head>
<body>

<?php include BASE_PATH . "/navbar.php"; ?>

<main class="page">
    <h2>Import result</h2>

    <?php if ($errors): ?>
        <p style="color:red;"><strong>Nothing was imported. Please fix these problems and upload again:</strong></p>
        <ul style="text-align:left;">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><strong>Import successful.</strong></p>
        <?php foreach ($summary as $line): ?>
            <p><?= htmlspecialchars($line) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <a class="button" href="/admin/import_csv.php">Back to import</a>
    <a class="button" href="/view_reagents.php">View reagents</a>
</main>
</body>
</html>
<?php $link->close(); ?>