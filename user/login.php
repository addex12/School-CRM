<?php
session_start();
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
    $password = $_POST['password'];

    $result = $conn->query("SELECT id, password, role FROM users WHERE username='$username'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid password.';
        }
    } else {
        $error = 'User not found.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login</title>
</head>
<body>
    <h1>User Login</h1>
    <?php if (isset($error)): ?>
        <p><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
