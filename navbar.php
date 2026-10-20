<?php
// Shared navbar: init.php is already loaded by most pages; load it if not.
require_once __DIR__ . '/init.php';
$role = (string) ($_SESSION['role'] ?? '');
$loggedIn = !empty($_SESSION['user_id']);
?>

<!-- Website title -->
<header>
    <h1>R e a g e n t l y</h1>
</header>

<nav class="navbar">
    <?php if ($loggedIn && $role === 'reagently_admin'): ?>
        <a href="<?= BASE_URL ?>platform_admin.php">Platform Admin</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <?php if ($loggedIn): ?>
            <a href="<?= BASE_URL ?>view_reagents.php">View reagents</a>
            <a href="<?= BASE_URL ?>user.php">User</a>
            <?php if ($role === 'admin'): ?>
                <a href="<?= BASE_URL ?>admin.php">Admin</a>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($loggedIn): ?>
        <a href="<?= BASE_URL ?>authentication/logout.php">Log out</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>authentication/login.php">Login</a>
        <a href="<?= BASE_URL ?>authentication/register_company.php">Register</a>
    <?php endif; ?>
</nav>
