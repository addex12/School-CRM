<?php
require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

function sendResetPasswordEmail($email, $resetToken) {
    $resetLink = "https://crm.flipperschool.com/reset_password.php?token=" . urlencode($resetToken);
    $subject = "Password Reset Request";
    $message = "Hello,\n\nWe received a request to reset your password. Click the link below to reset it:\n\n" . $resetLink . "\n\nIf you did not request this, please ignore this email.";
    $headers = "From: no-reply@yourdomain.com\r\n";
    $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    try {
        // Check if the email exists in the database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a reset token
            $resetToken = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
            $stmt->execute([$resetToken, $email]);

            // Send the reset email
            if (sendResetPasswordEmail($email, $resetToken)) {
                $success = "A password reset email has been sent to your email address.";
            } else {
                $error = "Failed to send the reset email. Please try again later.";
            }
        } else {
            $error = "No account found with that email address.";
        }
    } catch (Exception $e) {
        $error = "An error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .forgot-password-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .forgot-password-container h1 {
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
        .forgot-password-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .forgot-password-footer a {
            color: #3498db;
            text-decoration: none;
        }
        .forgot-password-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h1>Forgot Password</h1>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Send Reset Email</button>
        </form>
        <div class="forgot-password-footer">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>
