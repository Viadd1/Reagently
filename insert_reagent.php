<?php

/*
insert_reagent.php
Adds a new reagent to the database using data submitted from add_reagent.php.
*/


include 'db.php';


// Fetch data from POST request

$name =
    $_POST['name'];


$catalog_number =
    $_POST['catalog_number'];

$category =
    $_POST['category'];

$unit =
    $_POST['unit'];

$storage_condition =
    $_POST['storage_condition'];


// SQL statement

$sql =
"INSERT INTO reagents
(
    name,
    catalog_number,
    category,
    unit,
    storage_condition
)

VALUES (?, ?, ?, ?, ?)";


$stmt =
    $link->prepare($sql);


// First five variables are strings, last two are numbers

$stmt->bind_param(
    "sssss",
    $name,
    $catalog_number,
    $category,
    $unit,
    $storage_condition
);


// Try to insert the reagent
try {

    $result =
        $stmt->execute();


    // Reagent was created successfully
    if ($result) {

        echo "New reagent created successfully.";

        echo "<br><br>";

        echo
        '<a href="add_reagent.php">
            Add another reagent
        </a>';

        echo "<br>";

        echo
        '<a href="admin.php">
            Back to admin page
        </a>';

    }


} catch (mysqli_sql_exception $e) {


    // Error code 1062 means a UNIQUE value
    // already exists in the database
    if ($e->getCode() == 1062) {

        echo
            "A reagent with this catalog number already exists.";

        echo "<br><br>";

        echo
            "If you want to add a new batch of the same reagent,
            please add a new reagent lot instead.";

        echo "<br><br>";

        echo
            '<a href="add_lot.php">
                Add Reagent Lot
            </a>';

        echo "<br>";

        echo
            '<a href="add_reagent.php">
                Back to Add Reagent
            </a>';


    } else {


        // Show other database errors
        echo
            "Database error: " .
            $e->getMessage();

    }

}

$stmt->close();
$link->close();

?>