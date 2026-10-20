<?php
/**
 * Team cookie preferences controller (Accept / Reject optional cookies).
 * Keep this file output-free: setcookie() and header() must run before HTML.
 * Suitable for inclusion by both index.php and authentication/register.php.
 */
require_once __DIR__ . '/../init.php';
require_once BASE_PATH . '/db.php';

// Keep the original team's cookie name, values, and one-year retention.
$cookie_name = 'optional_cookie_consent';
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$consent = $_COOKIE[$cookie_name] ?? null;
if (!is_string($consent) || !in_array($consent, ['accepted', 'rejected'], true)) {
    $consent = null;
}

$cookie_options = [
    'expires' => time() + (365 * 24 * 60 * 60),
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
];

// Save the user's choice (browser cookie; also database when logged in).
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && array_key_exists('cookie_consent', $_POST)) {
    $choice = $_POST['cookie_consent'];
    if (!is_string($choice) || !in_array($choice, ['accepted', 'rejected'], true)) {
        http_response_code(400);
        exit('Invalid cookie preference.');
    }

    setcookie($cookie_name, $choice, $cookie_options);
    $consent = $choice;

    if ($user_id !== null && $user_id > 0) {
        try {
            $stmt = $link->prepare(
                'INSERT INTO cookie_consent (user_id, consent_status) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE consent_status = VALUES(consent_status)'
            );
            $stmt->bind_param('is', $user_id, $choice);
            $stmt->execute();
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // A missing cookie_consent table should not make public registration unusable.
            error_log('Unable to save Reagently cookie preference: ' . $e->getMessage());
        }
    }

    // Post/Redirect/Get: avoid resending the form on refresh.
    $return_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    if ($return_uri === '' || $return_uri[0] !== '/' || str_starts_with($return_uri, '//') || preg_match('/[\r\n]/', $return_uri)) {
        $return_uri = BASE_URL . 'index.php';
    }
    header('Location: ' . $return_uri, true, 303);
    exit;
}

// Restore the saved choice for an authenticated user on a new browser/session.
if ($consent === null && $user_id !== null && $user_id > 0) {
    try {
        $stmt = $link->prepare('SELECT consent_status FROM cookie_consent WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($saved_consent);
        if ($stmt->fetch() && in_array($saved_consent, ['accepted', 'rejected'], true)) {
            $consent = $saved_consent;
            setcookie($cookie_name, $consent, $cookie_options);
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Unable to load Reagently cookie preference: ' . $e->getMessage());
    }
}

$show_cookie_banner = ($consent !== 'accepted' && $consent !== 'rejected');
