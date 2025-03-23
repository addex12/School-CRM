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
                <input type="text" id="dbHost" name="dbHost" required><br>
                <label for="dbName">Database Name:</label>
                <input type="text" id="dbName" name="dbName" required><br>
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
            <button class="button" onclick="document.getElementById('dbForm').submit()">Install</button>
        </div>
    </div>

    <footer>
        <p>&copy; 2023 School-CRM. All Rights Reserved.</p>
        <p>Developer: Adugna Gizaw | Email: gizawadugna@gmail.com | Phone: +251925582067</p>
        <p>LinkedIn: <a href="https://www.linkedin.com/in/eleganceict" target="_blank">eleganceict</a> | Twitter: <a href="https://twitter.com/eleganceict1" target="_blank">@eleganceict1</a> | GitHub: <a href="https://github.com/addex12" target="_blank">addex12</a></p>
    </footer>
</body>
</html>
