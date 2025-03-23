<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */

include('../config/db_config.php');

// Handle form submissions for add, remove, update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $status = $_POST['status'];
        $query_add_user = "INSERT INTO users (username, status) VALUES ('$username', '$status')";
        mysqli_query($conn, $query_add_user);
    } elseif (isset($_POST['remove_user'])) {
        $user_id = $_POST['user_id'];
        $query_remove_user = "DELETE FROM users WHERE id = $user_id";
        mysqli_query($conn, $query_remove_user);
    } elseif (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];
        $query_update_user = "UPDATE users SET status = '$status' WHERE id = $user_id";
        mysqli_query($conn, $query_update_user);
    }
    // Repeat similar blocks for surveys, communications, and settings
}

// Fetch data from the database
$query_users = "SELECT COUNT(*) as total_users, 
                       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users, 
                       SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users 
                FROM users";
$result_users = mysqli_query($conn, $query_users);
$users = mysqli_fetch_assoc($result_users);

$query_surveys = "SELECT COUNT(*) as active_surveys, 
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_surveys, 
                         SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_surveys 
                  FROM surveys";
$result_surveys = mysqli_query($conn, $query_surveys);
$surveys = mysqli_fetch_assoc($result_surveys);

$query_communications = "SELECT COUNT(*) as messages_sent, 
                                SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread_messages, 
                                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_messages 
                         FROM communications";
$result_communications = mysqli_query($conn, $query_communications);
$communications = mysqli_fetch_assoc($result_communications);

$query_settings = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
$result_settings = mysqli_query($conn, $query_settings);
$settings = mysqli_fetch_assoc($result_settings);

?>
<div class="container">
<div class="dashboard-container">
        <h1>Admin Dashboard</h1>
        <nav class="dashboard-nav">
        <?php include('header.php'); ?>    
        </nav>
        <div class="dashboard-content">
            <!-- Dashboard Widgets -->
            <div class="widget">
                <h2>Users</h2>
                <p>Total Users: <?php echo $users['total_users']; ?></p>
                <button class="toggle-section" data-target="#user-details">View Details</button>
                <div id="user-details" class="hidden">
                    <p>Active Users: <?php echo $users['active_users']; ?></p>
                    <p>Inactive Users: <?php echo $users['inactive_users']; ?></p>
                </div>
                <!-- Add User Form -->
                <form method="POST">
                    <input type="text" name="username" placeholder="Username" required>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button type="submit" name="add_user">Add User</button>
                </form>
                <!-- Remove User Form -->
                <form method="POST">
                    <input type="number" name="user_id" placeholder="User ID" required>
                    <button type="submit" name="remove_user">Remove User</button>
                </form>
                <!-- Update User Form -->
                <form method="POST">
                    <input type="number" name="user_id" placeholder="User ID" required>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button type="submit" name="update_user">Update User</button>
                </form>
            </div>
            <div class="widget">
                <h2>Surveys</h2>
                <p>Active Surveys: <?php echo $surveys['active_surveys']; ?></p>
                <button class="toggle-section" data-target="#survey-details">View Details</button>
                <div id="survey-details" class="hidden">
                    <p>Completed Surveys: <?php echo $surveys['completed_surveys']; ?></p>
                    <p>Pending Surveys: <?php echo $surveys['pending_surveys']; ?></p>
                </div>
                <!-- Add Survey Form -->
                <!-- Similar forms for adding, removing, and updating surveys -->
            </div>
            <div class="widget">
                <h2>Communications</h2>
                <p>Messages Sent: <?php echo $communications['messages_sent']; ?></p>
                <button class="toggle-section" data-target="#communication-details">View Details</button>
                <div id="communication-details" class="hidden">
                    <p>Unread Messages: <?php echo $communications['unread_messages']; ?></p>
                    <p>Read Messages: <?php echo $communications['read_messages']; ?></p>
                </div>
                <!-- Add Communication Form -->
                <!-- Similar forms for adding, removing, and updating communications -->
            </div>
            <div class="widget">
                <h2>Settings</h2>
                <p>System Status: <?php echo $settings['system_status']; ?></p>
                <button class="toggle-section" data-target="#settings-details">View Details</button>
                <div id="settings-details" class="hidden">
                    <p>Last Backup: <?php echo $settings['last_backup']; ?></p>
                    <p>Next Maintenance: <?php echo $settings['next_maintenance']; ?></p>
                </div>
                <!-- Add Setting Form -->
                <!-- Similar forms for adding, removing, and updating settings -->
            </div>
        </div>
    </div>
    <?php include('footer.php'); ?>
</div>
<!-- Add necessary JavaScript files -->
<link rel="stylesheet" href="../style.css">
<script src="../js/dashboard.js"></script>