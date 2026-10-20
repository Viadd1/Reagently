<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/tenant_auth.php';
$companyId = tenant_require_admin($link);
tenant_require_post_csrf();
$projectId = filter_var($_POST['project_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!is_int($projectId) || !is_int($userId)) {
    http_response_code(400); exit('Invalid project or user.');
}
try {
    // Atomic conditional insert. Neither project nor user may be in a different company.
    $stmt = $link->prepare(
        "INSERT INTO project_members (project_id, user_id)
         SELECT p.project_id, u.user_id FROM projects p
         JOIN users u ON u.user_id = ? AND u.company_id = p.company_id
         WHERE p.project_id = ? AND p.company_id = ?
           AND u.role IN ('user','admin') AND u.approved = 1 AND u.is_verified = 1"
    );
    $stmt->bind_param('iii', $userId, $projectId, $companyId);
    $stmt->execute();
    $inserted = $stmt->affected_rows === 1;
    $stmt->close();
    $message = $inserted ? 'Project member added successfully.' : 'Project or user not found in your company.';
} catch (mysqli_sql_exception $e) {
    $message = (int)$e->getCode() === 1062 ? 'User is already a project member.' : 'Unable to add project member.';
}
$link->close();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Add Project Member</title>
<link rel="stylesheet" href="<?= BASE_URL ?>style.css"></head><body>
<?php include BASE_PATH . '/navbar.php'; ?><main class="page">
<h2><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></h2>
<a class="button" href="<?= BASE_URL ?>admin/add_project_member.php">Add another member</a>
</main></body></html>
