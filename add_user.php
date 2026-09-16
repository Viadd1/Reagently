<!DOCTYPE html>

<html>

<head>
    <title>Add User</title>
</head>

<body>

<h1>Add User</h1>

<form
    action="insert_user.php"
    method="POST"
>

    Name:
    <input
        type="text"
        name="name"
        required
    >

    <br><br>


    Lab group:
    <input
        type="text"
        name="lab"
    >

    <br><br>


    Role:
    <select
        name="role">
            <option value="user" selected>User</option>
            <option value="supervisor">Supervisor</option>
            <option value="admin">Admin</option>
    </select>

    <br><br>


    Email:
    <input
        type="text"
        name="email"
    >

    <br><br>


    Password:
    <input
        type="password"
        name="pwd"
    >

    <br><br>


    <input
        type="submit"
        value="Add User"
    >

</form>


<br>

<a href="index.php">
Back to home
</a>

</body>

</html>