<?php
/**
 * Admin Dashboard
 * 
 * This script displays the admin dashboard with all School-CRM features.
 * 
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin/login.php");
    exit();
}

// ...existing code...

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="/public/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the admin dashboard.</p>
        <div class="dashboard-menu">
            <h2>Features</h2>
            <ul>
                <li><a href="/admin/parent_survey.php">Parent Survey</a></li>
                <li><a href="/admin/teachers_survey.php">Teachers Survey</a></li>
                <li><a href="/admin/student_survey.php">Student Survey</a></li>
                <li><a href="/admin/communication_setup.php">Communication Setup</a></li>
                <li><a href="/admin/parent_setup.php">Parent Setup</a></li>
                <li><a href="/admin/student_setup.php">Student Setup</a></li>
                <li><a href="/admin/teachers_setup.php">Teachers Setup</a></li>
                <li><a href="/admin/account_management.php">Account Management</a></li>
                <li><a href="/admin/email_configuration.php">Email Configuration</a></li>
                <li><a href="/admin/module_configuration.php">Module Configuration</a></li>
                <li><a href="/admin/feature_management.php">Feature Management</a></li>
                <!-- Add more features as needed -->
            </ul>
        </div>
    </div>
</body>
</html>