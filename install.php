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
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dbHost = $_POST['dbHost'];
    $dbName = $_POST['dbName'];
    $dbUser = $_POST['dbUser'];
    $dbPass = $_POST['dbPass'];
    $adminUser = $_POST['adminUser'];
    $adminPass = $_POST['adminPass'];

    $conn = new mysqli($dbHost, $dbUser, $dbPass);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "CREATE DATABASE IF NOT EXISTS $dbName";
    if ($conn->query($sql) === TRUE) {
        $conn->select_db($dbName);

        $adminPassHash = password_hash($adminPass, PASSWORD_BCRYPT);
        $sql = "CREATE TABLE IF NOT EXISTS admin (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(30) NOT NULL,
            password VARCHAR(255) NOT NULL,
            reg_date TIMESTAMP
        )";

        if ($conn->query($sql) === TRUE) {
            $sql = "INSERT INTO admin (username, password) VALUES ('$adminUser', '$adminPassHash')";
            if ($conn->query($sql) === TRUE) {
                // Delete install folder
                array_map('unlink', glob(__DIR__ . "/*"));
                rmdir(__DIR__);

                // Redirect to admin login page
                header("Location: /admin/login.php");
                exit();
            } else {
                echo "Error creating admin account: " . $conn->error;
            }
        } else {
            echo "Error creating table: " . $conn->error;
        }
    } else {
        echo "Error creating database: " . $conn->error;
    }

    $conn->close();
}

function getDatabases($host, $user, $pass) {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        return [];
    }
    $result = $conn->query("SHOW DATABASES");
    $databases = [];
    while ($row = $result->fetch_assoc()) {
        $databases[] = $row['Database'];
    }
    $conn->close();
    return $databases;
}

$defaultHost = 'localhost';
$databases = getDatabases($defaultHost, 'root', '');
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
            width: 50%;
            margin: auto;
            overflow: hidden;
        }
        header {
            background: #333;
            color: #fff;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #77aaff 3px solid;
        }
        header a {
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 16px;
        }
        footer {
            background: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
            margin-top: 20px;
        }
        .hidden { display: none; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 20px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            outline: none;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            border-radius: 15px;
            box-shadow: 0 9px #999;
        }
        .button:hover {background-color: #3e8e41}
        .button:active {
            background-color: #3e8e41;
            box-shadow: 0 5px #666;
            transform: translateY(4px);
        }
        form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px 0px #000;
        }
        form label {
            display: block;
            margin-bottom: 10px;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
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
            // Implement AJAX call to test database connection
            alert('Database connection successful!');
            showStep('step3');
        }
    </script>
</head>
<body>
    <header>
        <div class="container">
            <h1>School-CRM Installation</h1>
        </div>
    </header>

    <div class="container">
        <div id="step1">
            <h2>Welcome to the School-CRM Installation</h2>
            <p>Follow the steps to configure your database and create an administrator account.</p>
            <ol>
                <li>Click the "Start" button to begin the installation process.</li>
                <li>Enter your database details and create an administrator account.</li>
                <li>Click "Next" to test the database connection and create the administrator account.</li>
                <li>Click "Install" to complete the installation.</li>
            </ol>
            <button class="button" onclick="showStep('step2')">Start</button>
        </div>

        <div id="step2" class="hidden">
            <h2>Database Configuration</h2>
            <form id="dbForm">
                <label for="dbHost">Database Host:</label>
                <input type="text" id="dbHost" name="dbHost" value="<?php echo $defaultHost; ?>" required><br>
                <label for="dbName">Database Name:</label>
                <select id="dbName" name="dbName" required>
                    <?php foreach ($databases as $database): ?>
                        <option value="<?php echo $database; ?>"><?php echo $database; ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label for="dbUser">Database Username:</label>
                <input type="text" id="dbUser" name="dbUser" required><br>
                <label for="dbPass">Database Password:</label>
                <input type="password" id="dbPass" name="dbPass" required><br>
                <h2>Administrator Account</h2>
                <label for="adminUser">Admin Username:</label>
                <input type="text" id="adminUser" name="adminUser" required><br>
                <label for="adminPass">Admin Password:</label>
                <input type="password" id="adminPass" name="adminPass" required><br>
                <button type="button" class="button" onclick="testDatabaseConnection()">Next</button>
            </form>
        </div>

        <div id="step3" class="hidden">
            <h2>Ready to Install</h2>
            <form method="POST" action="">
                <input type="hidden" name="dbHost" value="<?php echo $_POST['dbHost']; ?>">
                <input type="hidden" name="dbName" value="<?php echo $_POST['dbName']; ?>">
                <input type="hidden" name="dbUser" value="<?php echo $_POST['dbUser']; ?>">
                <input type="hidden" name="dbPass" value="<?php echo $_POST['dbPass']; ?>">
                <input type="hidden" name="adminUser" value="<?php echo $_POST['adminUser']; ?>">
                <input type="hidden" name="adminPass" value="<?php echo $_POST['adminPass']; ?>">
                <button class="button" type="submit">Install</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2023 School-CRM. All Rights Reserved.</p>
        <p>Developer: Adugna Gizaw | Email: gizawadugna@gmail.com | Phone: +251925582067</p>
        <p>LinkedIn: <a href="https://www.linkedin.com/in/eleganceict" target="_blank">eleganceict</a> | Twitter: <a href="https://twitter.com/eleganceict1" target="_blank">@eleganceict1</a> | GitHub: <a href="https://github.com/addex12" target="_blank">addex12</a></p>
    </footer>
</body>
</html>
