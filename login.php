<?php
// Ensure no output before this point
ob_start();

// Include configuration first
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
        $stmt = $pdo->prepare("SELECT id, username, password, role_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
    // Update last login
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    // Log the login action to audit_logs
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user['id'],
            'login',
            'User logged in',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log('Audit log insert failed (login): ' . $e->getMessage());
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['logged_in'] = true;

    // Redirect based on role
    if ($user['role_id'] == 1) { 
        header("Location: " . BASE_URL . "/admin/dashboard.php");
    } else if ($user['role_id'] >= 2) { 
        header("Location: " . BASE_URL . "/user/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/error.php");
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
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1>School CRM</h1>
                </div>
                <h2>Welcome Back!</h2>
                <p>Please login to access your account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username:
                    </label>
                    <input type="text" name="username" id="username" required autocomplete="username" placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password:
                    </label>
                    <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter your password">
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <span>Login</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php" class="register-link">Register here</a></p>
            </div>
        </div>
        
        <div class="login-illustration">
            <img src="assets/images/login-illustration.svg" alt="Education illustration">
            <div class="illustration-text">
                <h3>School CRM</h3>
                <p>Manage students, teachers, and classes with our comprehensive CRM solution</p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Flush output buffer
ob_end_flush();
?>