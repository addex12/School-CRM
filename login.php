<?php
session_start();

// Check if session is already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/auth.php';

$error = ''; // Initialize error variable to avoid undefined variable notice

// Check if user is logging in
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query database to check user credentials and role
    $stmt = $pdo->prepare("
        SELECT users.*, roles.role_name, roles.id AS role_id 
        FROM users 
        LEFT JOIN roles ON users.role_id = roles.id 
        WHERE users.username = :username
        AND users.active = 1
    ");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['avatar'] = $user['avatar'];
        
        // Update last login time
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $updateStmt->bindParam(':id', $user['id']);
        $updateStmt->execute();

        // Redirect based on role_id
        if ($user['role_id'] == 1) { // Role ID 1 is for admin
            header('Location: admin/dashboard.php');
            exit;
        } else {
            // For all other roles, redirect to user dashboard
            header('Location: user/dashboard.php');
            exit;
        }
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School CRM System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 5% auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 600;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
            font-size: 48px;
            color: #3498db;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .error-message {
            color: #e74c3c;
            background: #fadbd8;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        .login-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        .login-footer a {
            color: #3498db;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h1 class="login-title">School CRM System Login</h1>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">Login</button>
            </div>
        </form>
        
        <div class="login-footer">
            <p><a href="forgot_password.php">Forgot your password?</a></p>
        </div>
    </div>
</body>
</html>