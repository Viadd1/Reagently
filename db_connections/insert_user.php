<?php
/*
Creates a new user account using information submitted from register.php.
The password is checked and hashed before the user is added to the database.
Afterwards, a verification email is sent to the user's email address.
*/

require_once __DIR__ . "/../init.php";

// Ensure that page has been entered through a POST method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// Include database connection and send email function
include BASE_PATH . '/db.php';
require_once BASE_PATH . '/send_email.php';
use function Email\sendEmail;

function sendVerificationEmail(string $username, string $userEmail, mysqli $db): bool {
    // 1. Create token
    $token = bin2hex(random_bytes(32));

    // The token is only valid for 24 hours,
    // but we write +26 hours due to time zone difference
    $expiresAt = date('Y-m-d H:i:s', strtotime('+26 hours'));

    // 2. Save token
    $sql = 'UPDATE users 
            SET verification_token = ?, token_expires_at = ? 
            WHERE username = ?';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $token, $expiresAt, $username);
    $stmt->execute();

    // 3. Build link
    $link = BASE_URL . "/localhost/authentication/verify_email.php?token=" . $token;

    // 4. Send email
    $subject = "Please verify your email address";
    $body = "
        <h2>Welcome!</h2>
        <p>Click the button below to verify your email address.</p>
        <p><a href=\"$link\" style=\"background:#222;color:white;padding:12px 24px;
        text-decoration:none;border-radius:4px;\">Verify Email</a></p>
        <p>This link expires in 24 hours.</p>
        <p>If you did not register, ignore this email.</p>
    ";

    return sendEmail($userEmail, $username, $subject, $body);
}

?>

<!DOCTYPE html>
<html>
<head>

<!-- For usability on larger and smaller screens: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reagently - Registration</title>
    <!-- Use the existing stylesheet -->
    <link
        rel="stylesheet"
        href="<?=BASE_URL?>style.css"
    >
</head>
<body>

<?php
include BASE_PATH . "/navbar.php";

// Fetch data submitted from register.php
$username =
    $_POST['username'];

$firstname =
    $_POST['firstname'];

$lastname =
    $_POST['lastname'];

$lab =
    $_POST['lab'];

$email =
    $_POST['email'];

$password =
    $_POST['password'];

$confirm_password =
    $_POST['confirm_password'];

/* Check that user has not entered username 'null'
This is not allowed as it is reserved for a dummy user for deleted accounts */
if ($username === 'null') {
    die("Invalid username.");
}

// Ensure that username consists of alphanumeric characters only
if (!ctype_alnum($username)) {
    die("Username can only contain letters and numbers.");
}

// Ensure that email is of valid format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("$email is not a valid email address.");
}

// Check that password is 8 or more characters
if (strlen($password) < 8) {
    die("Password not long enough.");
}

// Check whether the two entered passwords match
if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Hash the password before storing it in the database
// The original password should never be stored directly
$hashed_password =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );

/* Before constructing and executing the SQL query for inserting users,
check if any users exist already in the database.
If no users exist, the first user becomes the admin.
Otherwise, the role is automatically assigned as user and approved=0. */
$sql_check_users = "SELECT *
                    FROM users";

$users = $link->query($sql_check_users);

if ($users->num_rows == 0) {

    $role = "admin";
    $approved = 1;

    $sql = 
    "INSERT INTO users
    (
        username,
        firstname,
        lastname,
        lab,
        email,
        password,
        role,
        approved
    )

    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare SQL statement
    $stmt =
        $link->prepare($sql);

    $stmt->bind_param(
        "sssssssi",
        $username,
        $firstname,
        $lastname,
        $lab,
        $email,
        $hashed_password,
        $role,
        $approved
    );    

} else {

    $sql =
    "INSERT INTO users
    (
        username,
        firstname,
        lastname,
        lab,
        email,
        password
    )

    VALUES (?, ?, ?, ?, ?, ?)";

    // Prepare SQL statement
    $stmt =
        $link->prepare($sql);

    $stmt->bind_param(
        "ssssss",
        $username,
        $firstname,
        $lastname,
        $lab,
        $email,
        $hashed_password
    );
}

// Execute the INSERT statement
$result =
    $stmt->execute();

// Check whether registration was successful
if ($result) {
    echo "Account requested successfully.<br>";

    if (!sendVerificationEmail($username, $email, $link)) {
        echo "Failed to send verification email.";
        exit;
    }

    echo "Verification email sent to " . $email . ".";
} else {

    // Display database error
    // For example, this may happen when an email address already exists because email is UNIQUE.
    echo "Error: " .
         $stmt->error;
}

session_destroy();

// Close prepared statement
$stmt->close();
// Close database connection
$link->close();
?>