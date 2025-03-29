<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/social_auth.php';

if (isLoggedIn()) {
    header("Location: " . ($_SESSION['role_id'] === 1 ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}

$error = '';

// Handle regular form registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username or email already exists.");
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $password_hash, 4]); // 4 is the default role for regular users

            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle social registration
if (isset($_GET['provider'])) {
    try {
        $socialUser = handleSocialLogin($_GET['provider']);
        
        // Check if user exists by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$socialUser['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Create new user with default role (4 for regular user)
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $socialUser['name'],
                $socialUser['email'],
                $password,
                4 // Default role (regular user)
            ]);
            $userId = $pdo->lastInsertId();
            $roleId = 4;
        } else {
            $userId = $user['id'];
            $roleId = (int)$user['role_id']; // Cast to integer
        }
        
        // Log user in
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $socialUser['name'];
        $_SESSION['role_id'] = $roleId;
        
        // Redirect based on role
        $redirect = ($roleId == 1) ? 'admin/dashboard.php' : 'user/dashboard.php';
        header("Location: " . $redirect);
        exit();
    } catch (Exception $e) {
        $error = "Social registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .register-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .register-title {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .register-logo {
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
        .error-message {
            color: #e74c3c;
            background: #fadbd8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .success-message {
            color: #16a34a;
            background: #dcfce7;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-logo">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h1 class="register-title">School CRM System Registration</h1>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="social-login">
            <a href="register.php?provider=google" class="social-btn google-btn">
                <i class="fab fa-google"></i>
            </a>
            <a href="register.php?provider=facebook" class="social-btn facebook-btn">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="register.php?provider=linkedin" class="social-btn linkedin-btn">
                <i class="fab fa-linkedin-in"></i>
            </a>
        </div>
        
        <div class="divider">
            <span class="divider-text">OR</span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </div>
        </form>
        
        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <?php include 'user/includes/footer.php'; ?>
</body>
</html>
