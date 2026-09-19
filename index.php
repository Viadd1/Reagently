<?php

// Start session so that the homepage knows
// whether a user is logged in
session_start();

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- =========================
     Website title
     ========================= -->

<header>

    <h1>Reagently</h1>

</header>



<!-- =========================
     Navigation bar
     ========================= -->

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

        <!-- Show Login and Register
             when nobody is logged in -->

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



<!-- =========================
     Welcome section
     ========================= -->

<section class="welcome">


    <?php

    // Personalized welcome message
    // for logged-in users

    if (isset($_SESSION['username'])) {

        echo
        "<h2>
            Welcome, " .
            htmlspecialchars(
                $_SESSION['username']
            ) .
            "!
        </h2>";

    } else {

        echo
        "<h2>
            Welcome to Reagently
        </h2>";

    }

    ?>


    <p>

        Reagently is a shared laboratory reagent
        management system for tracking reagent
        availability, storage locations,
        reservations and usage.

    </p>



    <?php

    // Show quick actions when the user is logged in
    if (isset($_SESSION['user_id'])) {

    ?>

        <div class="buttons">

            <a
                class="button"
                href="view_reagents.php"
            >
                View Reagents
            </a>


            <a
                class="button"
                href="make_reservation.php"
            >
                Make Reservation
            </a>


            <a
                class="button"
                href="user.php"
            >
                My Account
            </a>

        </div>


    <?php

    } else {

    ?>

        <div class="buttons">

            <a
                class="button"
                href="login.php"
            >
                Login
            </a>


            <a
                class="button"
                href="register.php"
            >
                Register
            </a>

        </div>


    <?php

    }

    ?>


</section>



<!-- =========================
     Recent updates
     ========================= -->

<section class="news">


    <h2>
        Recent Updates
    </h2>


    <div class="news-list">


        <div class="news-item">

            <h3>
                Welcome to Reagently
            </h3>

            <p class="news-date">
                September 2026
            </p>

            <p>

                Reagently provides a shared system
                for managing laboratory reagents,
                locations and availability.

            </p>

        </div>



        <div class="news-item">

            <h3>
                Reservation System
            </h3>

            <p class="news-date">
                September 2026
            </p>

            <p>

                Researchers can reserve available
                reagents for projects and record
                their final usage.

            </p>

        </div>


    </div>


</section>


</body>

</html>