<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Register';
// Use global $pdo from db.php, do not instantiate Database class

class AuthHelper {
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
}

$errors = [];
$username = $email = $role = '';

// Move POST handling outside of the AuthHelper check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Do not set $role at all, and do not assign any default

    // Validation
    if (empty($username)) $errors['username'] = "Username is required";
    if (empty($email)) $errors['email'] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format";
    if (empty($password)) $errors['password'] = "Password is required";
    if (strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors['confirm_password'] = "Passwords do not match";

    // Check if username or email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $errors['general'] = "Username or email already exists";
        }
    }

    // Set role_id to NULL explicitly (no default, no parent, no role)
    $role_id = null;

    // Set user as inactive by default (use 'active' column, not 'is_active')
    $active = 0;

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id, active) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$username, $email, $hashed_password, $role_id, $active])) {
            $_SESSION['success'] = "Registration successful! Please wait for admin approval before logging in.";
            header("Location: login.php");
            exit();
        } else {
            $errors['general'] = "Registration failed. Please try again.";
        }
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
        .register-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f8fb;
            padding: 0 10px;
        }
        .register-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px 32px 32px 32px;
            max-width: 420px;
            width: 100%;
        }
        .register-title {
            text-align: center;
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 18px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f9f9f9;
            font-size: 1em;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #217dbb;
        }
        .form-footer {
            text-align: center;
            margin-top: 18px;
            font-size: 0.98em;
        }
        .error-message, .success-message {
            padding: 10px 14px;
            border-radius: 5px;
            margin-bottom: 18px;
            font-size: 1em;
        }
        .error-message {
            background: #ffeaea;
            color: #d32f2f;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background: #e7fbe7;
            color: #388e3c;
            border: 1px solid #b2dfdb;
        }
        .register-logo {
            text-align: center;
            margin-bottom: 18px;
        }
        .register-logo i {
            font-size: 2.5rem;
            color: #3498db;
        }
        @media (max-width: 600px) {
            .register-card {
                padding: 24px 8px 18px 8px;
                max-width: 100%;
            }
            .register-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
<div class="register-wrapper">
    <div class="register-card">
        <div class="register-logo">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="register-title">Create Account</div>
        <?php if (!empty($errors['general'])): ?>
            <div class="error-message"><?= htmlspecialchars($errors['general']) ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username ?? '') ?>">
                <?php if (isset($errors['username'])): ?>
                    <div class="field-error"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="field-error"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="field-error"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="field-error"><?php echo $errors['confirm_password']; ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-primary">Register</button>
        </form>
        <div class="form-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>