<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['dashboard_path']);
    exit();
}

// Load UI configuration
$ui_config = json_decode(file_get_contents(__DIR__ . '/assets/config/ui.json'), true);
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.dashboard_path, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE (u.username = :id OR u.email = :id)
            AND u.active = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            
            // Set session data
            $_SESSION = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name'],
                'dashboard_path' => $user['dashboard_path']
            ];
            
            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);
            
            // Redirect to dashboard
            header("Location: " . $user['dashboard_path']);
            exit();
        } else {
            $error = 'Invalid credentials';
            log_activity(null, 'login_failed', "Failed login: $username");
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'System error. Please try later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Login - <?= htmlspecialchars($ui_config['site_name']) ?></title>
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="icon" href="<?= htmlspecialchars($ui_config['favicon']) ?>">
</head>
<body>
    <div class="login-container">
        <main class="login-card">
            <header class="login-header">
                <img src="<?= htmlspecialchars($ui_config['logo']) ?>" 
                     alt="Logo" 
                     class="logo"
                     loading="lazy">
                <h1><?= htmlspecialchars($ui_config['welcome_message']) ?></h1>
            </header>

            <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="input-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        <span>Username/Email</span>
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required
                           autocomplete="username"
                           value="<?= htmlspecialchars($username) ?>">
                </div>

                <div class="input-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        <span>Password</span>
                    </label>
                    <div class="password-wrapper">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               autocomplete="current-password">
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password" class="forgot-password">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <footer class="login-footer">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($ui_config['site_name']) ?></p>
                <nav>
                    <a href="/privacy">Privacy</a>
                    <a href="/terms">Terms</a>
                    <a href="/contact">Contact</a>
                </nav>
            </footer>
        </main>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>