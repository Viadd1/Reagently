<!-- DB 
Creates the connection. Other PHP files include this file when database access is needed. -->

<?php

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "Reagently";
$port = 8889;

$link = mysqli_connect(
    $servername,
    $username,
    $password,
    $dbname,
    $port
);

if (mysqli_connect_error()) {
    die(
        "Connection failed: " .
        mysqli_connect_error()
    );
}
?>