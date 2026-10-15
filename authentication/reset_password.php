<?php

require_once __DIR__ . "/../init.php";
require BASE_PATH . '/db.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Reset Password</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?=BASE_URL?>style.css"
    >
</head>
<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<!-- Main page content -->
<main class="page">

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    
    if (strlen($newPassword) < 8) {
        die('Password must be at least 8 characters.');
    }
    
    // Validate the token
    $sql_token = 'SELECT user_id
                  FROM password_resets
                  WHERE token = ?
                  AND expires_at > NOW()
                  AND used = 0';
    
    $stmt = $link->prepare($sql_token);
    $stmt->bind_param('s', $token);

    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if ($res->num_rows == 0) {
        die('This link is invalid or has expired.');

    } else {

        while ($row = $res->fetch_assoc()) {
            $user_id = $row["user_id"];

        }
    }
    
    // Update the password — always hash it with password_hash()
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql_pwd = 'UPDATE users
                SET password = ?
                WHERE user_id = ?';
    
    $stmt = $link->prepare($sql_pwd);
    $stmt->bind_param("si", $hash, $user_id);

    $stmt->execute();
    // Mark the token as used so it can't be used again
    $sql_reset = 'UPDATE password_resets
                  SET used = 1
                  WHERE token = ?';

    $stmt = $link->prepare($sql_reset);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    echo 'Password updated! <a href="login.php">Log in</a>';

    session_destroy();

    $stmt->close();
    $link->close();
    exit;
} 
?>

<!-- New password form -->
 <form method="POST">
    <input  type="hidden" name="token"
            value="<?= htmlspecialchars($token) ?>">

    <label>New password:
        <input  type="password"
                name="password"
                minlength="8" required>
    </label>
    <input class="button"
           type="submit"
           value="Set new password">
    </input>
</form>

</main>

</body>

</html>