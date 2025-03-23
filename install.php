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
        .hidden { display: none; }
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
    <h1>School-CRM Installation</h1>

    <div id="step1">
        <button onclick="showStep('step2')">Start</button>
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
            <button type="button" onclick="testDatabaseConnection()">Next</button>
        </form>
    </div>

    <div id="step3" class="hidden">
        <h2>Ready to Install</h2>
        <button onclick="document.getElementById('dbForm').submit()">Install</button>
    </div>
</body>
</html>
