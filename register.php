<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Register';

// Registration logic (simplified, add your own validation and error handling)
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Register user
            if (registerUser($username, $email, $password)) {
                $success = 'Registration successful! You can now <a href="login.php">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
    <style>
        .register-container {
            max-width: 420px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 32px 28px 28px 28px;
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-title">Create Account</div>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-primary">Register</button>
        </form>
        <div class="form-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>