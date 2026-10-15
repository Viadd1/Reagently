<!-- Init php -->

<?php

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the absolute path to the project root
define('BASE_PATH', __DIR__);
define('BASE_URL', '/');