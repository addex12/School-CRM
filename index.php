<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] === 'admin') {
    echo '<a href="admin/user_management.php">User Management</a><br>';
    echo '<a href="admin/system_settings.php">System Settings</a><br>';
}

?>
