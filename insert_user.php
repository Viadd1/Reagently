<?php

include 'db.php';


$name =
    $_POST['name'];

$lab =
    $_POST['lab'];

$role =
    $_POST['role'];

$email =
    $_POST['email'];

$password = 
   password_hash($_POST['pwd'], PASSWORD_BCRYPT);


$sql =
"INSERT INTO users
(
    name,
    lab,
    role,
    email,
    password
)

VALUES (?, ?, ?, ?, ?)";


$stmt =
    $link->prepare($sql);


$stmt->bind_param(
    "sssss",
    $name,
    $lab,
    $role,
    $email,
    $password
);


$result =
    $stmt->execute();


if ($result) {

    echo "New user created successfully.";

    echo '<br><br>';

    echo '<a href="add_user.php">
          Add another user
          </a>';

    echo '<br>';

    echo '<a href="index.php">
          Back to home
          </a>';

} else {

    echo "Error: " .
         $stmt->error;
}


$stmt->close();
$link->close();

?>