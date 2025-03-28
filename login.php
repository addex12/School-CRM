<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize social login configuration
require_once 'includes/social_auth.php'; // You'll need to create this file

if (isLoggedIn()) {
    header("Location: " . ($_SESSION['role'] === 'Admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}

$error = '';

// Handle regular form login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_id'];
        
        // Update last login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        header("Location: " . ($user['role_id'] === 'Admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
        exit();
    } else {
        $error = "Invalid username or password";
    }
}

// Handle social login callback
if (isset($_GET['provider'])) {
    $provider = $_GET['provider'];
    try {
        $socialUser = handleSocialLogin($provider); // Implement this function in social_auth.php
        
        // Check if user exists by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$socialUser['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Create new user
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $socialUser['name'],
                $socialUser['email'],
                $password,
                4 // Default role (e.g., 'User')
            ]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $user['id'];
        }
        
        // Log user in
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $socialUser['name'];
        $_SESSION['role'] = $user['role_id'] ?? 4;
        
        header("Location: " . ($_SESSION['role'] === 'Admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
        exit();
    } catch (Exception $e) {
        $error = "Social login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Survey System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
            font-size: 48px;
            color: #3498db;
        }
        .social-login {
            margin: 20px 0;
            text-align: center;
        }
        .social-btn {
            display: inline-block;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin: 0 5px;
            color: white;
            font-size: 20px;
            line-height: 45px;
            text-align: center;
            transition: all 0.3s;
        }
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .google-btn { background: #DB4437; }
        .facebook-btn { background: #4267B2; }
        .linkedin-btn { background: #0077B5; }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .divider-text {
            padding: 0 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-poll-h"></i>
        </div>
        <h1 class="login-title">Survey System Login</h1>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="social-login">
            <a href="login.php?provider=google" class="social-btn google-btn">
                <i class="fab fa-google"></i>
            </a>
            <a href="login.php?provider=facebook" class="social-btn facebook-btn">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="login.php?provider=linkedin" class="social-btn linkedin-btn">
                <i class="fab fa-linkedin-in"></i>
            </a>
        </div>
        
        <div class="divider">
            <span class="divider-text">OR</span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="forgot_password.php">Forgot your password?</a></p>
        </div>
    </div>

    <?php include 'user/includes/footer.php'; ?>
</body>
</html>