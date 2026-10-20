<?php
// Use this on every company-admin endpoint (and separately company-scoped
// authorization on all user/reagent/project/reservation endpoints in Phase 3).
function require_company_admin(mysqli $link): int {
    require_once __DIR__ . '/tenant_auth.php';
    return tenant_require_admin($link);
}
function company_csrf(): string {
    if (empty($_SESSION['company_admin_csrf'])) {
        $_SESSION['company_admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['company_admin_csrf'];
}
function company_require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('POST required.');
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(company_csrf(), $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}
function company_go(string $key, string $message): never {
    header('Location: ' . BASE_URL . 'admin/approve_users.php?' . $key . '=' . rawurlencode($message));
    exit;
}
