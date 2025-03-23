<?php
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
$host = 'localhost';
$user = 'flipperschool_crm';
$pass = 'A25582067s_';
$dbname = 'flipperschool_school_crm';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error . ' (' . $conn->connect_errno . ')');
}

// Fetch data for display (example for user management)
$users = $conn->query("SELECT id, username, role FROM users");

include 'header.php';
?>

    <h1>Admin Dashboard</h1>
    <section id="user-management">
        <h2>User Management</h2>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
            <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo $user['username']; ?></td>
                <td><?php echo $user['role']; ?></td>
                <td>
                    <a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a>
                    <a href="delete_user.php?id=<?php echo $user['id']; ?>">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <a href="create_user.php">Create New User</a>
    </section>

    <section id="communication-configuration">
        <h2>Communication Configuration</h2>
        <form method="post" action="save_communication_settings.php">
            <label for="email_notifications">Email Notifications:</label>
            <input type="checkbox" id="email_notifications" name="email_notifications">
            <br>
            <label for="sms_notifications">SMS Notifications:</label>
            <input type="checkbox" id="sms_notifications" name="sms_notifications">
            <br>
            <button type="submit">Save Settings</button>
        </form>
    </section>

    <section id="system-parameterization">
        <h2>System Parameterization</h2>
        <form method="post" action="save_system_settings.php">
            <label for="school_name">School Name:</label>
            <input type="text" id="school_name" name="school_name" required>
            <br>
            <label for="academic_year">Academic Year:</label>
            <input type="text" id="academic_year" name="academic_year" required>
            <br>
            <button type="submit">Save Settings</button>
        </form>
    </section>

    <section id="parent-survey-management">
        <h2>Parent Survey Management</h2>
        <a href="create_survey.php">Create New Survey</a>
        <br>
        <a href="view_surveys.php">View Surveys</a>
    </section>

<?php include 'footer.php'; ?>
</body>
</html>
