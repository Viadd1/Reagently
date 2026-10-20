<!-- DB connection - Delete Project Member 
Removes the submitted project member from that specific project. 
If the user has created a reservation to that specific project,
it is redirected to the null user.-->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Include database connection
include BASE_PATH . "/db.php";

// Ensure user has gone to this page through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$user_id = $_POST['user_id'];
$project_id = $_POST['project_id'];

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


/* Create a 'null' project member for the project if it doesn't already exist. */
$sql_pm = "SELECT *
           FROM project_members
           WHERE user_id = ?
           AND project_id = ?";

$stmt = $link->prepare($sql_pm);

$stmt->bind_param(
    "ii",
    $null_user_id,
    $project_id
);

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows == 0) {

    $null_pm = "INSERT INTO project_members (project_id, user_id)
                VALUES (?, ?)";
    
    $stmt = $link->prepare($null_pm);

    $stmt->bind_param(
        "ii",
        $project_id,
        $null_user_id
    );

    $stmt->execute();
    $stmt->close();
}

/* Reroute all reservations to the null user */
$sql_res = "UPDATE reservations
            SET user_id = ?
            WHERE user_id = ?
            AND project_id = ?";

$stmt = $link->prepare($sql_res);

$stmt->bind_param(
    "iii",
    $null_user_id,
    $user_id,
    $project_id
);

$stmt->execute();
$stmt->close();

/* Before removing the user, remove all project member entries linked to the user. */
$sql_rm_pm = "DELETE FROM project_members
              WHERE user_id = ?
              AND project_id = ?";

$stmt = $link->prepare($sql_rm_pm);

$stmt->bind_param(
    "ii",
    $user_id,
    $project_id
);

$delete = $stmt->execute();

// If unable to delete account, raise error
if (!$delete) {

    echo "Error: " . $stmt->error;
}

else {

?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Delete User</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>style.css"
    >
</head>
<body>

<?php

include BASE_PATH . "/navbar.php";

echo "Project member removed successfully.<br><br>
<a  class='button'
    href='/admin/manage_projects.php'>
    Back to Manage Projects
</a>";
}

$stmt->close();
$link->close();
?>