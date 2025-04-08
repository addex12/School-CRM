<?php
// Ensure no output before this point
ob_start();

// Start the session
session_start();
$error = '';

require_once __DIR__ . '/includes/config.php';

// Include required files
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Check if the user is already logged in
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php");
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
            
            // Get user role and set session
            $stmt = $pdo->prepare("SELECT users.*, roles.role_name 
                                FROM users 
                                JOIN roles ON users.role_id = roles.id 
                                WHERE users.id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();
            
            if ($userData) {
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['role_id'] = $userData['role_id'];
                $_SESSION['role'] = $userData['role_name'];
                
                // Redirect based on role
                if ($userData['role_id'] == 1) { 
                    header("Location: " . BASE_URL . "/admin/dashboard.php");
                } else if ($userData['role_id'] >= 2) { 
                    header("Location: " . BASE_URL . "/user/dashboard.php");
                } else {
                    header("Location: " . BASE_URL . "/error.php");
                }
                exit();
            }
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred. Please try again later.";
    }
}

// Start output buffering if not already started
if (!ob_get_level()) {
    ob_start();
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
        <?php if ($error): ?>
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

<?php
// Flush output buffer
ob_end_flush();
?>
