<?php
session_start();
$base_url = '/crm.flipperschool.com/';
$error = '';
// Include required files
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Check if the user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Get user from database
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Get user role
            $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $role = $stmt->fetch();
            
            // Redirect based on role
            if ($role['role_id'] == 1) { 
                header("Location: " . $base_url . "admin/dashboard.php");                
            } else if ($role['role_id'] >= 2) { 
                header("Location: " . $base_url . "user/dashboard.php");                
            } else {
                header("Location: " . $base_url . "index.php");
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Login to School CRM</h1>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-primary">Login</button>
            </div>
            <div class="form-group">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
