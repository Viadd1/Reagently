<?php
/** Create a project belonging to the logged-in Company Admin's company. */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';

// Verify company-level admin authorization and the submitted CSRF token.
$companyId = tenant_require_admin($link);
tenant_require_post_csrf();

$name = $_POST['project_name'] ?? null;
$description = $_POST['description'] ?? null;

if (!is_string($name) || !is_string($description)) {
    http_response_code(400);
    exit('Invalid project name or description.');
}

$name = trim($name);
if ($name === '' || strlen($name) > 255 || strtolower($name) === 'null') {
    http_response_code(400);
    exit('Invalid project name.');
}

$ok = false;
try {
    $stmt = $link->prepare(
        'INSERT INTO projects (company_id, project_name, description)
         VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iss', $companyId, $name, $description);
    $ok = $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log('Create project failed: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagently - Create Project</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>
<?php include BASE_PATH . '/navbar.php'; ?>
<main class="page">
    <h2><?= $ok ? 'Project created successfully.' : 'Unable to create project.' ?></h2>
    <a class="button" href="<?= BASE_URL ?>admin/add_project.php">Create another project</a>
    <a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>
</main>
</body>
</html>
<?php $link->close(); ?>
