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
                <td data-editable><?php echo $user['username']; ?></td>
                <td data-editable><?php echo $user['role']; ?></td>
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

    <section id="teacher-management">
        <h2>Teacher Management</h2>
        <a href="create_teacher.php">Create New Teacher</a>
        <br>
        <a href="view_teachers.php">View Teachers</a>
    </section>

    <section id="student-management">
        <h2>Student Management</h2>
        <a href="create_student.php">Create New Student</a>
        <br>
        <a href="view_students.php">View Students</a>
    </section>

    <section id="parents-management">
        <h2>Parents Management</h2>
        <a href="create_parent.php">Create New Parent</a>
        <br>
        <a href="view_parents.php">View Parents</a>
    </section>

    <section id="parent-dashboard">
        <h2>Parent Dashboard</h2>
        <a href="parent_dashboard.php">View Parent Dashboard</a>
    </section>

    <section id="feedback-concerns">
        <h2>Feedback and Concerns</h2>
        <a href="view_feedback.php">View Feedback and Concerns</a>
    </section>

    <section id="messaging-system">
        <h2>Messaging System</h2>
        <a href="view_messages.php">View Messages</a>
    </section>

    <section id="push-notifications">
        <h2>Push Notifications</h2>
        <form method="post" action="send_notifications.php">
            <label for="notification_message">Notification Message:</label>
            <textarea id="notification_message" name="notification_message" required></textarea>
            <br>
            <button type="submit">Send Notification</button>
        </form>
    </section>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("#user-management table tr").forEach(row => {
        row.querySelectorAll("td[data-editable]").forEach(cell => {
            cell.addEventListener("click", function() {
                this.contentEditable = true;
            });
            cell.addEventListener("blur", function() {
                this.contentEditable = false;
                // ...AJAX or form submission to update cell in DB...
            });
        });
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
