<?php
declare(strict_types=1);

// This file assumes init.php and db.php have been included by the caller.
function require_platform_admin(mysqli $link): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'authentication/login.php');
        exit;
    }

    // Do not trust stale session role alone: verify current permissions in the DB.
    $id = (int) $_SESSION['user_id'];
    $stmt = $link->prepare('SELECT role, approved FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u || $u['role'] !== 'reagently_admin' || (int) $u['approved'] !== 1) {
        http_response_code(403);
        exit('Access denied: Reagently platform administrator required.');
    }
}

function platform_csrf(): string
{
    if (empty($_SESSION['platform_csrf'])) {
        $_SESSION['platform_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['platform_csrf'];
}

function require_platform_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('POST required');
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(platform_csrf(), $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function platform_go(string $key, string $message): never
{
    header('Location: ' . BASE_URL . 'platform_admin.php?' . $key . '=' . rawurlencode($message));
    exit;
}
