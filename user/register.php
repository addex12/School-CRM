<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Database connection
    $host = 'localhost';
    $user = 'flipperschool_crm';
    $pass = 'A25582067s_';
    $dbname = 'flipperschool_school_crm';

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $conn->query("INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')");
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
</head>
<body>
    <h1>User Registration</h1>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <label for="role">Role:</label>
        <select id="role" name="role">
            <option value="student">Student</option>
            <option value="teacher">Teacher</option>
            <option value="parent">Parent</option>
        </select>
        <br>
        <button type="submit">Register</button>
    </form>
</body>
</html>
