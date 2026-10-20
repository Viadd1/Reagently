<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_require_post_csrf();
$name = $_POST['project_name'] ?? null;
$description = $_POST['description'] ?? null;
if (!is_string($name) || !is_string($description) || trim($name) === '' || strlen(trim($name)) > 255) {
    http_response_code(400); exit('Invalid project name or description.');
}
$name = trim($name);
try {
    $stmt = $link->prepare('INSERT INTO projects (company_id, project_name, description) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $companyId, $name, $description);
    $stmt->execute();
    $stmt->close();
    $ok = true;
} catch (mysqli_sql_exception $e) {
    error_log('Create project failed: ' . $e->getMessage());
    $ok = false;
}
$link->close();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Create Project</title>
<link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head><body>
<?php include BASE_PATH . '/navbar.php'; ?><main class="page">
<h2><?= $ok ? 'Project created successfully.' : 'Unable to create project.' ?></h2>
<a class="button" href="<?= BASE_URL ?>admin/add_project.php">Create another project</a>
<a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>
</main></body></html>
