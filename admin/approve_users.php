<?php
/*
Approve New Users
Lets an admin approve or reject the accounts of users who chose that admin
when they registered. An admin only sees and handles their own pending users.
*/

// Start session and set base path variable
require_once __DIR__ . "/../init.php";

// Include database connection
include BASE_PATH . "/db.php";

// Only logged-in admins may open this page
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Access denied. Admin permission is required.");
}

// The admin id comes from the session, never from the form
$admin_id = (int)$_SESSION['user_id'];


/* =========================
   Approve or reject a user
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = (int)($_POST['user_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $msg     = '';
    $error   = '';

    // The WHERE clause is the safety check: the user must be a pending,
    // normal account that chose THIS admin. Otherwise nothing is changed.
    if ($action === 'approve') {

        $stmt = $link->prepare(
            "UPDATE users SET approved = 1
             WHERE user_id = ? AND supervisor_id = ?
             AND approved = 0 AND role = 'user'");
        $stmt->bind_param("ii", $user_id, $admin_id);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            $msg = "User approved.";
        } else {
            $error = "This user could not be approved (not your pending user).";
        }
        $stmt->close();

    } elseif ($action === 'reject') {

        try {
            $stmt = $link->prepare(
                "DELETE FROM users
                 WHERE user_id = ? AND supervisor_id = ?
                 AND approved = 0 AND role = 'user'");
            $stmt->bind_param("ii", $user_id, $admin_id);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $msg = "Registration rejected and removed.";
            } else {
                $error = "This user could not be rejected (not your pending user).";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error = "Database error. The user could not be removed.";
        }

    } else {
        $error = "Unknown action.";
    }

    // Redirect so that refreshing the page does not resend the form
    $query = $error !== '' ? "error=" . urlencode($error) : "msg=" . urlencode($msg);
    header("Location: " . BASE_URL . "admin/approve_users.php?" . $query);
    exit;
}

$msg   = $_GET['msg']   ?? '';
$error = $_GET['error'] ?? '';


/* =========================
   Pending users of this admin
   ========================= */

$sql =
"SELECT
    user_id,
    username,
    firstname,
    lastname,
    lab,
    email,
    created_at

 FROM users

 WHERE supervisor_id = ?
 AND approved = 0
 AND role = 'user'

 ORDER BY created_at";

$stmt = $link->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>

    <title>Reagently - Approve New Users</title>

    <!-- Use the existing stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
</head>
<body>

<?php
// Main header and navigation bar
include BASE_PATH . "/navbar.php";
?>

<main class="page">

    <h2>Approve New Users</h2>

    <p>
        Registrations from users who chose you as their admin.
        Check the name, email and lab before approving.
    </p>

    <?php if ($msg !== ''): ?>
        <h5 style="color:darkgreen"><?= htmlspecialchars($msg) ?></h5>
    <?php elseif ($error !== ''): ?>
        <h5 style="color:red"><?= htmlspecialchars($error) ?></h5>
    <?php else: ?>
        <br>
    <?php endif; ?>

    <table border="1">

        <tr>
            <th>Username</th>
            <th>Name</th>
            <th>Lab</th>
            <th>Email</th>
            <th>Registered</th>
            <th>Action</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($u = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars(trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''))) ?></td>
                <td><?= htmlspecialchars($u['lab'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                    <form action="<?= BASE_URL ?>admin/approve_users.php"
                          method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <input class="button" type="submit" value="Approve">
                    </form>

                    <form action="<?= BASE_URL ?>admin/approve_users.php"
                          method="POST" style="display:inline;"
                          onsubmit="return confirm('Reject and remove this registration?');">
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input class="button" type="submit" value="Reject">
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>

        <?php else: ?>
            <tr>
                <td colspan="6">No users are waiting for your approval.</td>
            </tr>
        <?php endif; ?>

    </table>

    <br><br>

    <a class="button" href="<?= BASE_URL ?>admin.php">Back to Admin</a>

</main>
</body>
</html>
<?php
$stmt->close();
$link->close();
?>