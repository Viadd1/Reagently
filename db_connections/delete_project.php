<!-- DB connection - Delete Project 
Deletes the submitted project. If any project members
or reservations are linked to the project, they are first deleted.-->

<?php

// Start session and set base path variable
require_once __DIR__ . "/../init.php";


// Include database connection
include BASE_PATH . "/db.php";


// Ensure user has gone to this page through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$project_id = $_POST['project_id'];


// Check if there is a null project in the database
$sql_check_null = "SELECT project_id FROM projects
                   WHERE project_name = 'null'";

$result = $link->query($sql_check_null);


// Create null project in the database if it doesn't exist
if ($result->num_rows == 0) {

    $sql_create_null = "INSERT INTO projects (`project_name`)
                        VALUES ('null')";

    $create_null = $link->query($sql_create_null);

    if (!$create_null) {
        echo "Error: " . $link->error;
    }

    $result = $link->query($sql_check_null);
}


// There should now be one (1) null project in the database
if ($result->num_rows == 1) {
    while ($row = $result->fetch_assoc()) {
        $null_project_id = $row["project_id"];
    }
}

// Create project member entries for the null projects where necessary
$sql_pm = "SELECT user_id
           FROM project_members
           WHERE project_id = ?";

$stmt = $link->prepare($sql_pm);

$stmt->bind_param(
    "i",
    $project_id
);

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

while ($row = $result->fetch_assoc()) {

    $sql_ins_pm = "INSERT INTO project_members(`user_id`, `project_id`)
                   VALUES (?, ?)";
    $stmt = $link->prepare($sql_ins_pm);

    $stmt->bind_param(
        "ii",
        $row["user_id"],
        $null_project_id
    );

    $stmt->execute();
}

/* Reroute inventory transactions to the null project.
We want to preserve the inventory data since it is used for internal calculations. */
$sql_res = "UPDATE reservations
           SET project_id = ?
           WHERE project_id = ?";

$stmt = $link->prepare($sql_res);

$stmt->bind_param(
    "ii",
    $null_project_id,
    $project_id
);

$stmt->execute();
$stmt->close();


/* Before removing the project, remove all project member entries linked to the project. */
$sql_rm_pm = "DELETE FROM project_members
              WHERE project_id = ?";

$stmt = $link->prepare($sql_rm_pm);
$stmt->bind_param("i", $project_id);
$stmt->execute();


/* All links to the project have been removed or unlinked,
so we can now delete the project from the database. */
$sql_rm_proj = "DELETE FROM projects
                WHERE project_id = ?";

$stmt = $link->prepare($sql_rm_proj);
$stmt->bind_param("i", $project_id);
$delete = $stmt->execute();

// If unable to delete project, raise error
if (!$delete) {

    echo "Error: " . $stmt->error;
}

// Otherwise, show success message
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

echo "Project deleted successfully.<br><br>
<a  class='button'
    href='/admin/manage_projects.php'>
    Back to Manage Projects
</a>";

}

?>

</body>
</html>

<?php

$stmt->close();
$link->close();
?>