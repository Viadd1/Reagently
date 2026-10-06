<?php

/*--------------
Deletes the submitted user account and all information related to it.
If the user is linked to other database records we want to keep,
the user id is redirected to a null user created at the start of this script.
--------------*/

// Resume current session so that it can be destroyed in the end
session_start();


// Include database connection
include "db.php";


// Ensure user has gone to this page through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$user_id = $_POST['user_id'];


// Check if there is a null user in the database
$sql_check_null = "SELECT user_id FROM users
                   WHERE username = 'null'";

$result = $link->query($sql_check_null);


// Create null user in the database if it doesn't exist
if ($result->num_rows == 0) {

    $sql_create_null = "INSERT INTO users (`username`, `email`, `password`)
                        VALUES ('null', 'null@null', 'null')";

    $create_null = $link->query($sql_create_null);

    if (!$create_null) {
        echo "Error: " . $link->error;
    }

    $result = $link->query($sql_check_null);
}


// There should now be one (1) null user in the database
if ($result->num_rows == 1) {
    while ($row = $result->fetch_assoc()) {
        $null_user_id = $row["user_id"];
    }
}


/*----------
Delete order requests made by the user.
Since order requests are personal, they can be deleted without rerouting.
----------*/

$sql_or = "DELETE FROM order_requests
           WHERE user_id = ?";

$stmt = $link->prepare($sql_or);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$stmt->close();


/*----------
Delete nicknames made by the user if it is an admin.
Nicknames created by a specific user may be identifiable personal information,
so it's safest to remove them altogether.
----------*/

$sql_nn = "DELETE FROM reagent_nicknames
           WHERE created_by = ?";

$stmt = $link->prepare($sql_nn);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$stmt->close();


/*----------
Reroute inventory transactions to the null user.
We want to preserve the inventory data since it is used for internal calculations.
----------*/

$sql_it = "UPDATE inventory_transactions
           SET user_id = ?
           WHERE user_id = ?";

$stmt = $link->prepare($sql_it);

$stmt->bind_param(
    "ii",
    $null_user_id,
    $user_id
);

$stmt->execute();

$stmt->close();


/*------------
Create 'null' project members for each project the user is part of.
This is necessary for rerouting reservations.
------------*/

$sql_pm = "SELECT *
           FROM project_members
           WHERE user_id = ?";

$stmt = $link->prepare($sql_pm);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$stmt->close();

while ($row = $result->fetch_assoc()) {
        
    // For each project_id found, insert a 'null' project member if it doesn't already exist

    $pid = $row["project_id"];

    $check_null_pm = "SELECT *
                      FROM project_members
                      WHERE project_id = ? AND user_id = ?";
        
    $stmt = $link->prepare($check_null_pm);

    $stmt->bind_param(
        "ii",
        $pid,
        $null_user_id
    );

    $stmt->execute();

    $res = $stmt->get_result();

    if ($res->num_rows == 0) {

        $stmt->close();

        $null_pm = "INSERT INTO project_members (project_id, user_id)
                    VALUES (?, ?)";
    
        $stmt = $link->prepare($null_pm);

        $stmt->bind_param(
            "ii",
            $pid,
            $null_user_id
        );

        $stmt->execute();
    }
}


/*
Reroute all reservations to the null user
*/

$sql_res = "UPDATE reservations
            SET user_id = ?
            WHERE user_id = ?";

$stmt = $link->prepare($sql_res);

$stmt->bind_param(
    "ii",
    $null_user_id,
    $user_id
);

$stmt->execute();

/*-----------
Before removing the user, remove all project member entries linked to the user.
-----------*/

$sql_rm_pm = "DELETE FROM project_members
              WHERE user_id = ?";

$stmt = $link->prepare($sql_rm_pm);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

/*-------------
All links to the user account have been removed or unlinked,
so we can now delete the user from the database.
-------------*/

$sql_rm_user = "DELETE FROM users
                WHERE user_id = ?";

$stmt = $link->prepare($sql_rm_user);

$stmt->bind_param(
    "i",
    $user_id
);

$delete = $stmt->execute();


// If unable to delete account, raise error
if (!$delete) {

    echo "Error: " . $stmt->error;
}

// If the user deletes their own account, destroy session
elseif ($delete && $_SESSION['user_id'] == $user_id) {
    
    session_unset();

    session_destroy();

    header(
    "Location: login.php"
    );

    exit;
} 

// Otherwise, an admin deleted the account, so we do nothing

else {

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reagently - Admin</title>

    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<?php

include "navbar.php";

echo "Account deleted successfully.";

}

$stmt->close();

$link->close();

?>