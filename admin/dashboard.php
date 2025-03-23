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
    if (isset($_POST['survey_title']) && isset($_POST['survey_description'])) {
        $title = $_POST['survey_title'];
        $description = $_POST['survey_description'];
        
        $db = new mysqli('localhost', 'username', 'password', 'database');
        $stmt = $db->prepare("INSERT INTO surveys (title, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $description);
        $stmt->execute();
        $stmt->close();
        $db->close();
        
        echo "Survey created successfully!";
    } else {
        echo "Please provide a title and description for the survey.";
    }
}

function handleDeleteSurvey() {
    if (!isset($_POST['survey_id'])) {
        echo "Please provide a survey ID.";
        return;
    }

    $survey_id = $_POST['survey_id'];
    if (deleteSurveyById($survey_id)) {
        echo "Survey deleted successfully!";
    } else {
        echo "Failed to delete survey.";
    }
}

function deleteSurveyById($survey_id) {
    $params = [$survey_id];
    $query = "DELETE FROM surveys WHERE id = ?";
    return executeQuery($query, $params);
}
function handleEditSurvey() {
    if (!isset($_POST['survey_id'])) {
        echo "Please provide a survey ID.";
        return;
    }
    if (!isset($_POST['survey_title'])) {
        echo "Please provide a survey title.";
        return;
    }
    if (!isset($_POST['survey_description'])) {
        echo "Please provide a survey description.";
        return;
    }

    $survey_id = $_POST['survey_id'];
    $title = $_POST['survey_title'];
    $description = $_POST['survey_description'];
    
    $params = [$title, $description, $survey_id];
    $query = "UPDATE surveys SET title = ?, description = ? WHERE id = ?";
    
    if (executeQuery($query, $params)) {
        echo "Survey updated successfully!";
    } else {
        echo "Failed to update survey.";
    }
}
        
function handleUpdateStudent() {
    if (areStudentFieldsSet()) {
        $id = $_POST['student_id'];
        $name = $_POST['student_name'];
        $age = $_POST['student_age'];
        $class = $_POST['student_class'];
        
        $params = [$name, $age, $class, $id];
        $query = "UPDATE students SET name = ?, age = ?, class = ? WHERE id = ?";
        
        if (executeQuery($query, $params)) {
            echo "Student updated successfully!";
        } else {
            echo "Failed to update student.";
        }
    } else {
        echo "Please provide a student ID, name, age, and class.";
    }
}

function areStudentFieldsSet() {
    return isset($_POST['student_id']) && isset($_POST['student_name']) && isset($_POST['student_age']) && isset($_POST['student_class']);
}
    if (areStudentFieldsSet()) {
        $id = $_POST['student_id'];
        $name = $_POST['student_name'];
        $age = $_POST['student_age'];
        $class = $_POST['student_class'];
        
        $params = [$name, $age, $class, $id];
        $query = "UPDATE students SET name = ?, age = ?, class = ? WHERE id = ?";
        
        if (executeQuery($query, $params)) {
            echo "Student updated successfully!";
        } else {
            echo "Failed to update student.";
        }
    } else {
        echo "Please provide a student ID, name, age, and class.";
    }
}

    if (areStudentFieldsSet()) {
    return isset($_POST['student_id']) && isset($_POST['student_name']) && isset($_POST['student_age']) && isset($_POST['student_class']);
}
    $isStudentIdSet = isset($_POST['student_id']);
    $isStudentNameSet = isset($_POST['student_name']);
    $isStudentAgeSet = isset($_POST['student_age']);
    $isStudentClassSet = isset($_POST['student_class']);
    
    if ($isStudentIdSet) {
        if ($isStudentNameSet) {
            if ($isStudentAgeSet) {
                if ($isStudentClassSet) {
        $id = $_POST['student_id'];
        $name = $_POST['student_name'];
function handleUpdateStudent() {
    $isStudentIdSet = isset($_POST['student_id']);
function handleUpdateStudent() {
    $isStudentIdSet = isset($_POST['student_id']);
    $isStudentNameSet = isset($_POST['student_name']);
    $isStudentAgeSet = isset($_POST['student_age']);
    $isStudentClassSet = isset($_POST['student_class']);
    
    if ($isStudentIdSet && $isStudentNameSet && $isStudentAgeSet && $isStudentClassSet) {
        $id = $_POST['student_id'];
        $name = $_POST['student_name'];
        $age = $_POST['student_age'];
        $class = $_POST['student_class'];
        
        $params = [$name, $age, $class, $id];
        $query = "UPDATE students SET name = ?, age = ?, class = ? WHERE id = ?";
        
        if (executeQuery($query, $params)) {
            echo "Student updated successfully!";
                } else {
                    echo "Please provide a student class.";
                }
            } else {
                echo "Please provide a student age.";
            }
        } else {
            echo "Please provide a student name.";
        }
    } else {
        echo "Please provide a student ID.";
    }
    } else {
        echo "Please provide a student ID, name, age, and class.";
    }
}
        $name = $_POST['teacher_name'];
        $subject = $_POST['teacher_subject'];
        
        $params = [$name, $subject, $id];
        $query = "UPDATE teachers SET name = ?, subject = ? WHERE id = ?";
        
        if (executeQuery($query, $params)) {
            echo "Teacher updated successfully!";
        } else {
        echo "Please provide a parent ID, name, and contact.";
function handleUpdateParent() {
    if (isset($_POST['parent_id']) && isset($_POST['parent_name']) && isset($_POST['parent_contact'])) {
        $id = $_POST['parent_id'];
        $name = $_POST['parent_name'];
        $contact = $_POST['parent_contact'];
        
        $params = [$name, $contact, $id];
        $query = "UPDATE parents SET name = ?, contact = ? WHERE id = ?";
        
        if (executeQuery($query, $params)) {
            echo "Parent updated successfully!";
        } else {
            echo "Failed to update parent.";
        }
    } else {
        echo "Please provide a parent ID, name, and contact.";
    }
}

function executeQuery($query, $params) {
    $db = new mysqli('localhost', 'username', 'password', 'database');
    if ($db->connect_error) {
        return false;
        return false;
    }
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $result = $stmt->execute();
    $stmt->close();
    $db->close();
    return $result;
}

include('../database/db_config.php');
include('session.php');

// Fetch teachers data
$sql = "SELECT * FROM teachers";
$result = mysqli_query($db, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .dashboard-menu {
            margin-bottom: 20px;
        }
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
        <h2>Teachers List</h2>
        <table border="1">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Subjects</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['subjects']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <footer>
        <p>&copy; 2025 School-CRM. All rights reserved.</p>
    </footer>
</body>
</html>