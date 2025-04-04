<?php
// Enable error reporting for this script
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Debugging: Log the email being checked
    error_log("Attempting login for email: $email");

    // Adjust the query to match the actual column names in the database
    $stmt = $pdo->prepare("SELECT id, full_name AS username, role_id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Debugging: Log if the user was found
        error_log("User found for email: $email");
        error_log("Role ID: " . $user['role_id']); // Log the role ID
        error_log("Database password hash: " . $user['password']); // Log the hashed password

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username']; // Use 'full_name' as 'username'
            $_SESSION['role_id'] = $user['role_id'];

            if ($user['role_id'] == 1) {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        } else {
            // Debugging: Log if the password verification failed
            error_log("Password verification failed for email: $email");
        }
    } else {
        // Debugging: Log if no user was found
        error_log("No user found for email: $email");
    }

    $error = "Invalid email or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
