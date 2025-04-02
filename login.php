<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['user_id'] == 1 ? '/admin/dashboard.php' : '/user/dashboard.php'));
    exit();
}

// Initialize variables
$error = '';
$username = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $query = "
            SELECT u.*, r.dashboard_path, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE (u.username = :username OR u.email = :username)
            AND u.active = 1
            LIMIT 1
        ";
        $stmt = executeQuery($query, [':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role'] = $user['role_name']; // Ensure role is set correctly
            $_SESSION['dashboard_path'] = $user['dashboard_path'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $updateStmt->execute([':id' => $user['id']]);
            
            // Log activity
            log_activity($user['id'], 'login', "User logged in");
            
            // Redirect to dashboard
            header("Location: " . ($user['role_name'] === 'admin' ? '/admin/dashboard.php' : $user['dashboard_path']));
            exit();
        } else {
            $error = 'Invalid username or password';
            log_activity(null, 'login_failed', "Failed login attempt for username: $username");
        }
    }
}

// Load UI configuration
$ui_config = json_decode(file_get_contents(__DIR__ . '/assets/config/ui.json'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($ui_config['site_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="icon" href="<?= htmlspecialchars($ui_config['favicon']) ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="<?= htmlspecialchars($ui_config['logo']) ?>" alt="Logo" class="logo">
                <h1><?= htmlspecialchars($ui_config['welcome_message']) ?></h1>
                <p><?= htmlspecialchars($ui_config['login_prompt']) ?></p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($username) ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <?php if ($ui_config['show_social_login']): ?>
                <div class="social-login">
                    <p>Or sign in with:</p>
                    <div class="social-buttons">
                        <?php foreach ($ui_config['social_providers'] as $provider): ?>
                            <a href="/auth/<?= strtolower($provider) ?>.php" class="social-button <?= strtolower($provider) ?>">
                                <i class="fab fa-<?= strtolower($provider) ?>"></i> <?= $provider ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($ui_config['show_register_link']): ?>
                <div class="register-link">
                    Don't have an account? <a href="/register.php">Contact administrator</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="login-footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($ui_config['site_name']) ?>. All rights reserved.</p>
            <div class="footer-links">
                <a href="/privacy.php">Privacy Policy</a>
                <a href="/terms.php">Terms of Service</a>
                <a href="/contact.php">Contact Us</a>
            </div>
        </div>
    </div>

    <script src="/assets/js/login.js"></script>
</body>
</html>