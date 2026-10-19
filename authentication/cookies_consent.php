<!-- Cookies concent. Lets users accept or reject optional cookies -->

<?php
require_once "init.php";
include_once "db.php";

// Check if the user is logged in
$user_id = $_SESSION['user_id'] ?? null;

// Name of the cookie stored in the browser
$cookie_name = "optional_cookie_consent";

// Check if the user has chosen an option
$consent = $_COOKIE[$cookie_name] ?? null;

// Handle the user's choice
if (isset($_POST['cookie_consent'])) {

    $choice = $_POST['cookie_consent'];

    // Only allow two choices
    if (
        $choice == "accepted" ||
        $choice == "rejected"
    ) {

        // Save the choice in the browser for one year
        setcookie(
            $cookie_name,
            $choice,
            time() + (365 * 24 * 60 * 60),
            "/",
            "",
            !empty($_SERVER['HTTPS']),
            true
        );

        // Save the choice in the database if the user is logged in
        if ($user_id !== null) {

            $stmt = $link->prepare(
                "INSERT INTO cookie_consent
                    (user_id, consent_status)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE
                    consent_status = VALUES(consent_status)"
            );

            $stmt->bind_param("is", $user_id, $choice);
            $stmt->execute();
            $stmt->close();
        }

        // Reload the page to hide the banner
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// If no browser cookie exists, check the database
if ($consent === null && $user_id !== null) {

    $stmt = $link->prepare(
        "SELECT consent_status
         FROM cookie_consent
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($saved_consent);

    if ($stmt->fetch()) {

        $consent = $saved_consent;

        // Restore the saved choice in the browser
        setcookie(
            $cookie_name,
            $consent,
            time() + (365 * 24 * 60 * 60),
            "/",
            "",
            !empty($_SERVER['HTTPS']),
            true
        );
    }

    $stmt->close();
}

// Show the banner if no choice has been made
$show_cookie_banner = (
    $consent !== "accepted" &&
    $consent !== "rejected"
);
?>