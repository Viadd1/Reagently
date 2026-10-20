<?php
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';
require_once BASE_PATH . '/includes/platform_auth.php';
require_platform_admin($link);
require_platform_post();

$name = trim((string) ($_POST['company_name'] ?? ''));
if ($name === '' || strlen($name) > 255) {
    platform_go('error', 'Enter a company name of at most 255 bytes.');
}

try {
    $stmt = $link->prepare("INSERT INTO companies (company_name, status) VALUES (?, 'PENDING')");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->close();
    platform_go('msg', 'Company created as PENDING: ' . $name);
} catch (mysqli_sql_exception $e) {
    platform_go('error', $e->getCode() == 1062 ? 'Company name already exists.' : 'Database error.');
}
