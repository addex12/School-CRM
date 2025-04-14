<?php
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['token'])) {
    $password = trim($_POST['password']);
    $token = trim($_POST['token']);

    // Validate the token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Update the user's password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        $success = "Your password has been reset successfully. You can now log in.";
    } else {
        $error = "Invalid or expired token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .reset-password-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .reset-password-container h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .error-message, .success-message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .error-message {
            background: #fadbd8;
            color: #e74c3c;
        }
        .success-message {
            background: #d4efdf;
            color: #27ae60;
        }
        .reset-password-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .reset-password-footer a {
            color: #3498db;
            text-decoration: none;
        }
        .reset-password-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <h1>Reset Password</h1>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
            <div class="form-group">
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Reset Password</button>
        </form>
        <div class="reset-password-footer">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>
