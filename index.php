<?php

// Main homepage of Reagently.
// It shows navigation options, a welcome message, quick links, and recent department updates.

// Start session so that the homepage knows whether a user is logged in
session_start();

include 'db.php';

// Latest 10 announcements, newest first
$announcements = $link->query(
    "SELECT title, message, author_name, created_at
     FROM announcements
     ORDER BY created_at DESC
     LIMIT 10"
);

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Home</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<!-- Website title -->

<header>

    <h1>Reagently</h1>

</header>


<!-- Navigation bar -->

<nav class="navbar">

    <a href="index.php">
        Home
    </a>

    <?php

    // Show these links only when a user is logged in
    if (isset($_SESSION['user_id'])) {

    ?>

        <a href="view_reagents.php">
            View reagents
        </a>

        <a href="user.php">
            User
        </a>

        <?php

        // Only admins see the Admin link
        if (
            isset($_SESSION['role'])
            &&
            $_SESSION['role'] === 'admin'
        ) {

        ?>

            <a href="admin.php">
                Admin
            </a>

        <?php

        }

        ?>

        <a href="logout.php">
            Log out
        </a>

    <?php

    } else {

    ?>

        <!-- Show Login and Register when nobody is logged in -->

        <a href="login.php">
            Login
        </a>

        <a href="register.php">
            Register
        </a>

    <?php

    }

    ?>

</nav>

<!-- Welcome section -->

<section class="welcome">

    <?php

    // Personalized welcome message for logged-in users
    if (isset($_SESSION['username'])) {

        echo
        "<h2>
            Welcome " .
            htmlspecialchars(
                $_SESSION['username']
            ) .
            "!
        </h2>";

    } else {
        echo
        "<h2>
            Welcome to Reagently!
        </h2>
        <br>
        <br>

        <h3>
        Please log in to access the Reagently web.
        </h3>";

    }

    ?>

    <br>
    <br>

    <p>

        Reagently is a shared laboratory reagent
        management system for tracking reagent
        availability, storage locations,
        reservations and usage.

    </p>

</section>

<!-- Department news -->

<?php 
if (isset($_SESSION['user_id'])) { 
?>

<section class="news">
<?php

    // Personalized department in news section for logged-in users
    if (isset($_SESSION['lab'])) {

        echo
        "<h2>
            Recent Updates from your lab: " .
            htmlspecialchars(
                $_SESSION['lab']
            ) .
            "
        </h2>";

    } else {

        echo
        "<h2>
            Recent Updates from your lab:
        </h2>";

    }

    ?>

    <div class="news-list">

        <?php if ($announcements->num_rows > 0): ?>

            <?php while ($a = $announcements->fetch_assoc()): ?>

                <article class="news-item">

                    <h3><?= htmlspecialchars($a['title']) ?></h3>

                    <p class="news-date">
                        <?= date('j F Y', strtotime($a['created_at'])) ?>
                        &middot; by <?= htmlspecialchars($a['author_name']) ?>
                    </p>

                    <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No announcements yet.</p>

        <?php endif; ?>

    </div>

</section>

<?php } 
?>

</body>

</html>

<?php $link->close(); ?>