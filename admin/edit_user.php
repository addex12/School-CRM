<!DOCTYPE html>
<html>
<?php
// filepath: /home/orbalia/School-CRM/admin/edit_user.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once __DIR__ . '/../config/db.php';

// Get user ID from the query string
$user_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch user data
$sql = "SELECT id, username, role, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['username'];
    $new_role = $_POST['role'];
    $new_email = $_POST['email'];

    // Update user data
    $sql = "UPDATE users SET username = ?, role = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $new_username, $new_role, $new_email, $user_id);

    if ($stmt->execute()) {
        echo "User updated successfully!";
        header('Location: dashboard.php'); // Redirect to dashboard
        exit();
    } else {
        echo "Error updating user: " . $stmt->error;
    }
}

include 'header.php';
?>

    <h1>Edit User</h1>

    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>

        <label for="role">Role:</label>
        <select id="role" name="role">
            <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
            <option value="teacher" <?php echo ($user['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
            <option value="student" <?php echo ($user['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
            <option value="parent" <?php echo ($user['role'] == 'parent') ? 'selected' : ''; ?>>Parent</option>
        </select><br>

        <button type="submit">Update User</button>
    </form>

<?php include 'footer.php'; ?>
</body>
</html>