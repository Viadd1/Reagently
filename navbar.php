<!-- Navbar -->

<?php
require_once "init.php";
?>

<!-- Website title -->
<header>
    <h1>Reagently</h1>
</header>

<nav class="navbar">

    <a href="<?= BASE_URL ?>index.php">
        Home
    </a>

    <?php
    // Show these links only when a user is logged in
    if (isset($_SESSION['user_id'])) {
    ?>

        <a href="<?= BASE_URL ?>view_reagents.php">
            View reagents
        </a>

        <a href="<?= BASE_URL ?>user.php">
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

            <a href="<?= BASE_URL ?>admin.php">
                Admin
            </a>

        <?php
        }
        ?>

        <a href="<?= BASE_URL ?>logout.php">
            Log out
        </a>

    <?php
    } else {
    ?>

        <!-- Show Login and Register when nobody is logged in -->
        <a href="<?= BASE_URL ?>login.php">
            Login
        </a>

        <a href="<?= BASE_URL ?>register.php">
            Register
        </a>

    <?php
    }
    ?>

</nav>