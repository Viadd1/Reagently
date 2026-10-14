<!-- Init php -->

<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the absolute path to the project root
define('BASE_PATH', __DIR__);
define('BASE_URL', '/');