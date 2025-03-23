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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_survey'])) {
        // Handle create survey logic here
    } elseif (isset($_POST['delete_survey'])) {
        // Handle delete survey logic here
    } elseif (isset($_POST['edit_survey'])) {
        // Handle edit/update survey logic here
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../style.css">
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .grid-item {
            border: 1px solid #ccc;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the admin dashboard.</p>
        <div class="dashboard-menu">
            <h2>Features Menu</h2>
            <ul>
                <li><a href="#parent_survey">Parent Survey</a></li>
                <li><a href="#teachers_survey">Teachers Survey</a></li>
                <li><a href="#student_survey">Student Survey</a></li>
                <li><a href="#create_survey">Create Survey</a></li>
                <li><a href="#delete_survey">Delete Survey</a></li>
                <li><a href="#edit_survey">Edit/Update Survey</a></li>
                <li><a href="#communication_setup">Communication Setup</a></li>
                <li><a href="#parent_setup">Parent Setup</a></li>
                <li><a href="#student_setup">Student Setup</a></li>
                <li><a href="#teachers_setup">Teachers Setup</a></li>
                <li><a href="#account_management">Account Management</a></li>
                <li><a href="#email_configuration">Email Configuration</a></li>
                <li><a href="#module_configuration">Module Configuration</a></li>
                <li><a href="#feature_management">Feature Management</a></li>
                <!-- Add more features as needed -->
            </ul>
        </div>
        <div class="grid-container">
            <div class="grid-item" id="parent_survey">
                <h2>Parent Survey</h2>
                <!-- Add content for Parent Survey -->
            </div>
            <div class="grid-item" id="teachers_survey">
                <h2>Teachers Survey</h2>
                <!-- Add content for Teachers Survey -->
            </div>
            <div class="grid-item" id="student_survey">
                <h2>Student Survey</h2>
                <!-- Add content for Student Survey -->
            </div>
            <div class="grid-item" id="create_survey">
                <h2>Create Survey</h2>
                <form method="post">
                    <!-- Add form fields for creating a survey -->
                    <input type="hidden" name="create_survey" value="1">
                    <button type="submit">Create Survey</button>
                </form>
            </div>
            <div class="grid-item" id="delete_survey">
                <h2>Delete Survey</h2>
                <form method="post">
                    <!-- Add form fields for deleting a survey -->
                    <input type="hidden" name="delete_survey" value="1">
                    <button type="submit">Delete Survey</button>
                </form>
            </div>
            <div class="grid-item" id="edit_survey">
                <h2>Edit/Update Survey</h2>
                <form method="post">
                    <!-- Add form fields for editing/updating a survey -->
                    <input type="hidden" name="edit_survey" value="1">
                    <button type="submit">Edit/Update Survey</button>
                </form>
            </div>
            <div class="grid-item" id="communication_setup">
                <h2>Communication Setup</h2>
                <!-- Add content for Communication Setup -->
            </div>
            <div class="grid-item" id="parent_setup">
                <h2>Parent Setup</h2>
                <!-- Add content for Parent Setup -->
            </div>
            <div class="grid-item" id="student_setup">
                <h2>Student Setup</h2>
                <!-- Add content for Student Setup -->
            </div>
            <div class="grid-item" id="teachers_setup">
                <h2>Teachers Setup</h2>
                <!-- Add content for Teachers Setup -->
            </div>
            <div class="grid-item" id="account_management">
                <h2>Account Management</h2>
                <!-- Add content for Account Management -->
            </div>
            <div class="grid-item" id="email_configuration">
                <h2>Email Configuration</h2>
                <!-- Add content for Email Configuration -->
            </div>
            <div class="grid-item" id="module_configuration">
                <h2>Module Configuration</h2>
                <!-- Add content for Module Configuration -->
            </div>
            <div class="grid-item" id="feature_management">
                <h2>Feature Management</h2>
                <!-- Add content for Feature Management -->
            </div>
            <!-- Add more grid items as needed -->
        </div>
    </div>
</body>
</html>