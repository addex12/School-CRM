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

function createDatabaseTables($conn) {
    $sql = "
    CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        teacher_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
    );

    CREATE TABLE IF NOT EXISTS enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        class_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (class_id) REFERENCES classes(id)
    );

    CREATE TABLE IF NOT EXISTS admins (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";

    if ($conn->multi_query($sql) === TRUE) {
        echo "<script>alert('Tables created successfully! Proceeding to admin user creation...'); window.location.href = '/admin/create_admin.php';</script>";
    } else {
        echo "Error creating tables: " . $conn->error;
    }
}

function createDbConfigFile($dbHost, $dbName, $dbUser, $dbPass) {
    $configContent = "<?php\n";
    $configContent .= "define('DB_HOST', '$dbHost');\n";
    $configContent .= "define('DB_NAME', '$dbName');\n";
    $configContent .= "define('DB_USER', '$dbUser');\n";
    $configContent .= "define('DB_PASS', '$dbPass');\n";
    $configContent .= "?>";

    file_put_contents(__DIR__ . '/config/db_config.php', $configContent);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['dbHost'];
    $dbName = $_POST['dbName'];
    $dbUser = $_POST['dbUser'];
    $dbPass = $_POST['dbPass'];

    $conn = new mysqli($dbHost, $dbUser, $dbPass);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->query("CREATE DATABASE IF NOT EXISTS $dbName");
    $conn->select_db($dbName);

    createDatabaseTables($conn);
    createDbConfigFile($dbHost, $dbName, $dbUser, $dbPass);

    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>School-CRM Installation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        h1, h2 {
            color: #333;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #5cb85c;
            color: #fff;
            border: none;
            border-radius: 5px;
        }
        button:hover {
            background-color: #4cae4c;
        }
        .hidden { display: none; }
        .progress-bar {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-bar-inner {
            height: 20px;
            width: 0;
            background-color: #5cb85c;
            text-align: center;
            color: white;
            line-height: 20px;
            border-radius: 5px;
        }
    </style>
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

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    alert('Database connection successful!');
                    showStep('step3');
                } else {
                    alert('Database connection failed!');
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
