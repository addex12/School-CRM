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
        handleCreateSurvey();
    } elseif (isset($_POST['delete_survey'])) {
        handleDeleteSurvey();
    } elseif (isset($_POST['edit_survey'])) {
        handleEditSurvey();
    } elseif (isset($_POST['create_student'])) {
        handleCreateStudent();
    } elseif (isset($_POST['create_teacher'])) {
        handleCreateTeacher();
    } elseif (isset($_POST['create_parent'])) {
        handleCreateParent();
    } elseif (isset($_POST['update_student'])) {
        handleUpdateStudent();
    } elseif (isset($_POST['update_teacher'])) {
        handleUpdateTeacher();
    } elseif (isset($_POST['update_parent'])) {
        handleUpdateParent();
    }
}

function handleCreateSurvey() {
    // Handle create survey logic here
}

function handleDeleteSurvey() {
    // Handle delete survey logic here
}

function handleEditSurvey() {
    // Handle edit/update survey logic here
}

function handleCreateStudent() {
    // Handle create student logic here
}

function handleCreateTeacher() {
    // Handle create teacher logic here
}

function handleCreateParent() {
    // Handle create parent logic here
}

function handleUpdateStudent() {
    // Handle update student logic here
}

function handleUpdateTeacher() {
    // Handle update teacher logic here
}

function handleUpdateParent() {
    // Handle update parent logic here
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
            cursor: pointer;
        }
        .grid-item form {
            display: none;
        }
        .grid-item.editable form {
            display: block;
        }
        header, footer {
            background-color: #f1f1f1;
            padding: 20px;
            text-align: center;
        }
    </style>
    <script>
        function toggleEdit(id) {
            var element = document.getElementById(id);
            element.classList.toggle('editable');
        }
    </script>
</head>
<body>
    <header>
        <h1>School-CRM Admin Dashboard</h1>
    </header>
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
                <li><a href="#create_student">Create Student</a></li>
                <li><a href="#create_teacher">Create Teacher</a></li>
                <li><a href="#create_parent">Create Parent</a></li>
                <li><a href="#update_student">Update Student</a></li>
                <li><a href="#update_teacher">Update Teacher</a></li>
                <li><a href="#update_parent">Update Parent</a></li>
                <!-- Add more features as needed -->
            </ul>
        </div>
        <div class="grid-container">
            <div class="grid-item" id="parent_survey" onclick="toggleEdit('parent_survey')">
                <h2>Parent Survey</h2>
                <form method="post">
                    <!-- Add form fields for Parent Survey -->
                    <input type="hidden" name="parent_survey" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="teachers_survey" onclick="toggleEdit('teachers_survey')">
                <h2>Teachers Survey</h2>
                <form method="post">
                    <!-- Add form fields for Teachers Survey -->
                    <input type="hidden" name="teachers_survey" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="student_survey" onclick="toggleEdit('student_survey')">
                <h2>Student Survey</h2>
                <form method="post">
                    <!-- Add form fields for Student Survey -->
                    <input type="hidden" name="student_survey" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="create_survey" onclick="toggleEdit('create_survey')">
                <h2>Create Survey</h2>
                <form method="post">
                    <!-- Add form fields for creating a survey -->
                    <input type="hidden" name="create_survey" value="1">
                    <button type="submit">Create Survey</button>
                </form>
            </div>
            <div class="grid-item" id="delete_survey" onclick="toggleEdit('delete_survey')">
                <h2>Delete Survey</h2>
                <form method="post">
                    <!-- Add form fields for deleting a survey -->
                    <input type="hidden" name="delete_survey" value="1">
                    <button type="submit">Delete Survey</button>
                </form>
            </div>
            <div class="grid-item" id="edit_survey" onclick="toggleEdit('edit_survey')">
                <h2>Edit/Update Survey</h2>
                <form method="post">
                    <!-- Add form fields for editing/updating a survey -->
                    <input type="hidden" name="edit_survey" value="1">
                    <button type="submit">Edit/Update Survey</button>
                </form>
            </div>
            <div class="grid-item" id="communication_setup" onclick="toggleEdit('communication_setup')">
                <h2>Communication Setup</h2>
                <form method="post">
                    <!-- Add form fields for Communication Setup -->
                    <input type="hidden" name="communication_setup" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="parent_setup" onclick="toggleEdit('parent_setup')">
                <h2>Parent Setup</h2>
                <form method="post">
                    <!-- Add form fields for Parent Setup -->
                    <input type="hidden" name="parent_setup" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="student_setup" onclick="toggleEdit('student_setup')">
                <h2>Student Setup</h2>
                <form method="post">
                    <!-- Add form fields for Student Setup -->
                    <input type="hidden" name="student_setup" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="teachers_setup" onclick="toggleEdit('teachers_setup')">
                <h2>Teachers Setup</h2>
                <form method="post">
                    <!-- Add form fields for Teachers Setup -->
                    <input type="hidden" name="teachers_setup" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="account_management" onclick="toggleEdit('account_management')">
                <h2>Account Management</h2>
                <form method="post">
                    <!-- Add form fields for Account Management -->
                    <input type="hidden" name="account_management" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="email_configuration" onclick="toggleEdit('email_configuration')">
                <h2>Email Configuration</h2>
                <form method="post">
                    <!-- Add form fields for Email Configuration -->
                    <input type="hidden" name="email_configuration" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="module_configuration" onclick="toggleEdit('module_configuration')">
                <h2>Module Configuration</h2>
                <form method="post">
                    <!-- Add form fields for Module Configuration -->
                    <input type="hidden" name="module_configuration" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="feature_management" onclick="toggleEdit('feature_management')">
                <h2>Feature Management</h2>
                <form method="post">
                    <!-- Add form fields for Feature Management -->
                    <input type="hidden" name="feature_management" value="1">
                    <button type="submit">Save</button>
                </form>
            </div>
            <div class="grid-item" id="create_student" onclick="toggleEdit('create_student')">
                <h2>Create Student</h2>
                <form method="post">
                    <!-- Add form fields for creating a student -->
                    <input type="hidden" name="create_student" value="1">
                    <button type="submit">Create Student</button>
                </form>
            </div>
            <div class="grid-item" id="create_teacher" onclick="toggleEdit('create_teacher')">
                <h2>Create Teacher</h2>
                <form method="post">
                    <!-- Add form fields for creating a teacher -->
                    <input type="hidden" name="create_teacher" value="1">
                    <button type="submit">Create Teacher</button>
                </form>
            </div>
            <div class="grid-item" id="create_parent" onclick="toggleEdit('create_parent')">
                <h2>Create Parent</h2>
                <form method="post">
                    <!-- Add form fields for creating a parent -->
                    <input type="hidden" name="create_parent" value="1">
                    <button type="submit">Create Parent</button>
                </form>
            </div>
            <div class="grid-item" id="update_student" onclick="toggleEdit('update_student')">
                <h2>Update Student</h2>
                <form method="post">
                    <!-- Add form fields for updating a student -->
                    <input type="hidden" name="update_student" value="1">
                    <button type="submit">Update Student</button>
                </form>
            </div>
            <div class="grid-item" id="update_teacher" onclick="toggleEdit('update_teacher')">
                <h2>Update Teacher</h2>
                <form method="post">
                    <!-- Add form fields for updating a teacher -->
                    <input type="hidden" name="update_teacher" value="1">
                    <button type="submit">Update Teacher</button>
                </form>
            </div>
            <div class="grid-item" id="update_parent" onclick="toggleEdit('update_parent')">
                <h2>Update Parent</h2>
                <form method="post">
                    <!-- Add form fields for updating a parent -->
                    <input type="hidden" name="update_parent" value="1">
                    <button type="submit">Update Parent</button>
                </form>
            </div>
            <!-- Add more grid items as needed -->
        </div>
    </div>
    <footer>
        <p>&copy; 2025 School-CRM. All rights reserved.</p>
    </footer>
</body>
</html>