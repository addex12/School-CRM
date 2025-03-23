<?php
/**
 * Admin Account Creation Script
 * 
 * This script guides the user through the administrator account creation process.
 * 
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */

require_once '../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminUser = $_POST['adminUser'];
    $adminPass = password_hash($_POST['adminPass'], PASSWORD_BCRYPT);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $adminUser, $adminPass);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    echo "<script>alert('Admin account created successfully! Redirecting to admin login...'); window.location.href = '/admin/login.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Account Creation</title>
    <link rel="stylesheet" type="text/css" href="/public/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 600px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        h1, h2 {
            color: #333;
            text-align: center;
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
            width: 100%;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #5cb85c;
            color: #fff;
            border: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #4cae4c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Account Creation</h1>
        <form method="POST">
            <label for="adminUser">Admin Username:</label>
            <input type="text" id="adminUser" name="adminUser" required><br>
            <label for="adminPass">Admin Password:</label>
            <input type="password" id="adminPass" name="adminPass" required><br>
            <button type="submit">Create Admin Account</button>
        </form>
    </div>
</body>
</html>
