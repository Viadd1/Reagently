<?php
/** Shared CSRF helpers for the User Management pages in this patch.
 *  Uses the existing $_SESSION['csrf_token'] set on Reagently's user.php.
 *  Does not rely on tenant_csrf_token() which is missing in some copies.
 */
if (!function_exists('reagently_user_csrf_token')) {
    function reagently_user_csrf_token(): string {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('reagently_require_user_post_csrf')) {
    function reagently_require_user_post_csrf(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('POST required.');
        }
        $given = $_POST['csrf_token'] ?? null;
        if (!is_string($given) || !hash_equals(reagently_user_csrf_token(), $given)) {
            http_response_code(403);
            exit('Invalid CSRF token. Please reload the page and try again.');
        }
    }
}
