<?php
/**
 * School-CRM Installation Script
 * 
 * This script guides the user through the installation process,
 * including database configuration and administrator account creation.
 * 
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */
?>
<?php
// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function createDatabaseTables($conn) {
    $tableCreationFunctions = [
        'createStudentsTable',
        'createTeachersTable',
        'createClassesTable',
        'createEnrollmentsTable',
        'createAdminsTable',
        'createParentSurveysTable',
        'createTeacherSurveysTable',
        'createStudentSurveysTable',
        'createCommunicationSetupTable',
        'createParentSetupTable',
        'createStudentSetupTable',
        'createTeacherSetupTable',
        'createAccountManagementTable',
        'createEmailConfigurationTable',
        'createModuleConfigurationTable',
        'createFeatureManagementTable'
    ];

    foreach ($tableCreationFunctions as $function) {
        $function($conn);
    }

    $tables = [
        "CREATE TABLE IF NOT EXISTS parents (
            parent_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            first_name VARCHAR(255) NULL,
            last_name VARCHAR(255) NULL,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            message TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES parents(parent_id),
            FOREIGN KEY (receiver_id) REFERENCES parents(parent_id)
        )",
        "CREATE TABLE IF NOT EXISTS feedback (
            feedback_id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES parents(parent_id)
        )",
        "CREATE TABLE IF NOT EXISTS surveys (
            survey_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_date DATE,
            end_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS survey_responses (
            response_id INT AUTO_INCREMENT PRIMARY KEY,
            survey_id INT NOT NULL,
            parent_id INT NOT NULL,
            response TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (survey_id) REFERENCES surveys(survey_id),
            FOREIGN KEY (parent_id) REFERENCES parents(parent_id)
        )",
        "CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT,
            message TEXT NOT NULL,
            type ENUM('email', 'sms') NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES parents(parent_id)
        )",
        "CREATE TABLE IF NOT EXISTS events (
            event_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS announcements (
            announcement_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS students (
            student_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            date_of_birth DATE NOT NULL,
            parent_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES parents(parent_id)
        )",
        "CREATE TABLE IF NOT EXISTS classes (
            class_id INT AUTO_INCREMENT PRIMARY KEY,
            class_name VARCHAR(255) NOT NULL,
            teacher_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS enrollments (
            enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            class_id INT NOT NULL,
            enrollment_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id),
            FOREIGN KEY (class_id) REFERENCES classes(class_id)
        )"
    ];

    foreach ($tables as $sql) {
        executeQuery($conn, $sql);
    }

    echo "<script>alert('Tables created successfully! Proceeding to admin user creation...'); window.location.href = '/admin/create_admin.php';</script>";
}

function createTable($conn, $tableName, $columns) {
    $sql = "CREATE TABLE IF NOT EXISTS $tableName ($columns);";
    executeQuery($conn, $sql);
}

function createEntityTable($conn, $tableName, $additionalColumns = '') {
    $columns = "
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        $additionalColumns
    ";
    createTable($conn, $tableName, $columns);
}

function createStudentsTable($conn) {
    createEntityTable($conn, 'students');
}

function createTeachersTable($conn) {
    createEntityTable($conn, 'teachers');
}

function createClassesTable($conn) {
    $columns = "
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        teacher_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
    ";
    createTable($conn, 'classes', $columns);
}

function createEnrollmentsTable($conn) {
    $columns = "
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        class_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (class_id) REFERENCES classes(id)
    ";
    createTable($conn, 'enrollments', $columns);
}

function createAdminsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );";
    executeQuery($conn, $sql);
}

function createSurveyTable($conn, $tableName) {
    $sql = "CREATE TABLE IF NOT EXISTS $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    executeQuery($conn, $sql);
}

function createParentSurveysTable($conn) {
    createSurveyTable($conn, 'parent_surveys');
}

function createTeacherSurveysTable($conn) {
    createSurveyTable($conn, 'teacher_surveys');
}

function createStudentSurveysTable($conn) {
    createSurveyTable($conn, 'student_surveys');
}

function createSetupTable($conn, $tableName, $columns) {
    $sql = "CREATE TABLE IF NOT EXISTS $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY,
        $columns,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    executeQuery($conn, $sql);
}

function createCommunicationSetupTable($conn) {
    createSetupTable($conn, 'communication_setup', "
        type VARCHAR(50) NOT NULL,
        details TEXT NOT NULL
    ");
}

function createParentSetupTable($conn) {
    createSetupTable($conn, 'parent_setup', "
        parent_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL
    ");
}

function createStudentSetupTable($conn) {
    createSetupTable($conn, 'student_setup', "
        student_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL
    ");
}

function createTeacherSetupTable($conn) {
    createSetupTable($conn, 'teacher_setup', "
        teacher_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL
    ");
}

function createAccountManagementTable($conn) {
    createSetupTable($conn, 'account_management', "
        account_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL
    ");
}

function createEmailConfigurationTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS email_configuration (
        id INT AUTO_INCREMENT PRIMARY KEY,
        smtp_server VARCHAR(255) NOT NULL,
        port INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    executeQuery($conn, $sql);
}

function createModuleConfigurationTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS module_configuration (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_name VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    executeQuery($conn, $sql);
}

function createFeatureManagementTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS feature_management (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feature_name VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    executeQuery($conn, $sql);
}

function executeQuery($conn, $sql) {
    if ($conn->query($sql) !== TRUE) {
        echo "Error creating table: " . $conn->error;
    }
}

function createDbConfigFile($dbHost, $dbName, $dbUser, $dbPass) {
    $configContent = "<?php\n";
    $configContent .= "/**\n";
    $configContent .= " * Database Configuration\n";
    $configContent .= " * This file contains the database configuration settings.\n";
    $configContent .= " * It is used to establish a connection to the database.\n";
    $configContent .= " * Developer: Adugna Gizaw\n";
    $configContent .= " * Email: gizawadugna@gmail.com\n";
    $configContent .= " * Phone: +251925582067\n";
    $configContent .= " * GitHub: https://github.com/addex12\n";
    $configContent .= " * LinkedIn: https://www.linkedin.com/in/eleganceict\n";
    $configContent .= " * Twitter: https://twitter.com/eleganceict1\n";
    $configContent .= " * @package School-CRM\n";
    $configContent .= " */\n";
    $configContent .= "\$servername = '$dbHost';\n";
    $configContent .= "\$username = '$dbUser';\n";
    $configContent .= "\$password = '$dbPass';\n";
    $configContent .= "\$dbname = '$dbName';\n";
    $configContent .= "\n";
    $configContent .= "// Create connection\n";
    $configContent .= "\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);\n";
    $configContent .= "\n";
    $configContent .= "// Check connection\n";
    $configContent .= "if (\$conn->connect_error) {\n";
    $configContent .= "    die('Connection failed: ' . \$conn->connect_error);\n";
    $configContent .= "}\n";
    $configContent .= "?>";

    file_put_contents(__DIR__ . '/config/db_config.php', $configContent);
}

function createDbConnectionFile() {
    $dbConnectionContent = "<?php\n";
    $dbConnectionContent .= "require_once __DIR__ . '/../config/db_config.php';\n";
    $dbConnectionContent .= "\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n";
    $dbConnectionContent .= "if (\$conn->connect_error) {\n";
    $dbConnectionContent .= "    die('Connection failed: ' . \$conn->connect_error);\n";
    $dbConnectionContent .= "}\n";
    $dbConnectionContent .= "?>";

    file_put_contents(__DIR__ . '/../includes/db.php', $dbConnectionContent);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'test_connection') {
        $dbHost = $_POST['dbHost'];
        $dbUser = $_POST['dbUser'];
        $dbPass = $_POST['dbPass'];

        try {
            $conn = new mysqli($dbHost, $dbUser, $dbPass);
            echo "Connection successful";
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            echo "Connection failed: " . $e->getMessage();
        }

        $conn->close();
        exit();
    }

    $dbHost = $_POST['dbHost'];
    $dbName = $_POST['dbName'];
    $dbUser = $_POST['dbUser'];
    $dbPass = $_POST['dbPass'];

    try {
        $conn = new mysqli($dbHost, $dbUser, $dbPass);

        $conn->query("CREATE DATABASE IF NOT EXISTS $dbName");
        $conn->select_db($dbName);

        createDatabaseTables($conn);
        createDbConfigFile($dbHost, $dbName, $dbUser, $dbPass);
        createDbConnectionFile();

        $conn->close();

        // Delete install.php file and redirect to admin login page
        unlink(__FILE__);
        header('Location: /admin/login.php');
        exit();
    } catch (mysqli_sql_exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>School-CRM Installation</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <script>
        function showStep(step) {
            document.getElementById('step1').classList.add('hidden');
            document.getElementById('step2').classList.add('hidden');
            document.getElementById('step3').classList.add('hidden');
            document.getElementById(step).classList.remove('hidden');
        }

        function testDatabaseConnection() {
            var form = document.getElementById('dbForm');
            var formData = new FormData(form);
            formData.append('action', 'test_connection');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    alert('Database connection successful!');
                    showStep('step3');
                } else {
                    alert('Database connection failed: ' + xhr.responseText);
                }
            };
            xhr.send(formData);
        }

        function updateProgressBar(progress) {
            var progressBar = document.getElementById('progressBarInner');
            progressBar.style.width = progress + '%';
            progressBar.innerText = progress + '%';
        }

        function startInstallation() {
            showStep('step3');
            updateProgressBar(0);

            setTimeout(function() { updateProgressBar(25); }, 1000);
            setTimeout(function() { updateProgressBar(50); }, 2000);
            setTimeout(function() { updateProgressBar(75); }, 3000);
            setTimeout(function() { 
                updateProgressBar(100); 
                document.getElementById('dbForm').submit();
            }, 4000);
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>School-CRM Installation</h1>

        <div id="step1">
            <h2>Welcome to the School-CRM Installation</h2>
            <p>Follow the steps to configure your database and create an administrator account.</p>
            <p>Developer: Adugna Gizaw</p>
            <p>Email: gizawadugna@gmail.com</p>
            <p>Phone: +251925582067</p>
            <p>GitHub: <a href="https://github.com/addex12" target="_blank">https://github.com/addex12</a></p>
            <p>LinkedIn: <a href="https://www.linkedin.com/in/eleganceict" target="_blank">https://www.linkedin.com/in/eleganceict</a></p>
            <p>Twitter: <a href="https://twitter.com/eleganceict1" target="_blank">https://twitter.com/eleganceict1</a></p>
            <button onclick="showStep('step2')">Start</button>
        </div>

        <div id="step2" class="hidden">
            <h2>Database Configuration</h2>
            <form id="dbForm" method="POST">
                <label for="dbHost">Database Host:</label>
                <input type="text" id="dbHost" name="dbHost" required><br>
                <label for="dbName">Database Name:</label>
                <input type="text" id="dbName" name="dbName" required><br>
                <label for="dbUser">Database Username:</label>
                <input type="text" id="dbUser" name="dbUser" required><br>
                <label for="dbPass">Database Password:</label>
                <input type="password" id="dbPass" name="dbPass" required><br>
                <button type="button" onclick="testDatabaseConnection()">Next</button>
            </form>
        </div>

        <div id="step3" class="hidden">
            <h2>Ready to Install</h2>
            <div class="progress-bar">
                <div id="progressBarInner" class="progress-bar-inner">0%</div>
            </div>
            <button onclick="startInstallation()">Install</button>
        </div>
    </div>
</body>
</html>
