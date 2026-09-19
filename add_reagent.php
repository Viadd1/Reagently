<!DOCTYPE html>

<html>

<head>
    <title>Add Reagent</title>
</head>

<body>

<h1>Add Reagent</h1>

<form
    action="insert_reagent.php"
    method="POST"
>

    Name:
    <input
        type="text"
        name="name"
        required
    >

    <br><br>



    Catalog number:
    <input
        type="text"
        name="catalog_number"
    >

    <br><br>


    Category:
    <input
        type="text"
        name="category"
    >

    <br><br>


    Unit:
    <input
        type="text"
        name="unit"
        placeholder="mL, g, mg..."
        required
    >

    <br><br>


    Storage condition:
    <input
        type="text"
        name="storage_condition"
        placeholder="4°C, -20°C, room temp..."
    >

    <br><br>


    <input
        type="submit"
        value="Add Reagent"
    >

</form>


<br>

<a href="index.php">
Back to home
</a>

</body>

</html>