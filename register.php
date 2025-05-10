<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 * Patent rights reserved.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Register';

// Fetch settings for site_name and banner
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_name', 'site_banner')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
// Use global $pdo from db.php, do not instantiate Database class

class AuthHelper {
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
}

$errors = [];
$email = '';

// Move POST handling outside of the AuthHelper check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($email)) $errors['email'] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format";

    // Check if email exists as username or email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$email, $email]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $errors['email'] = "Email address is already registered";
        }
    }

    if (empty($password)) $errors['password'] = "Password is required";
    if (strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors['confirm_password'] = "Passwords do not match";

    // Assign "new" role if it exists, otherwise NULL
    $role_id = null;
    $role_stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
    $role_stmt->execute(['new']);
    $role_row = $role_stmt->fetch(PDO::FETCH_ASSOC);
    if ($role_row && isset($role_row['id'])) {
        $role_id = $role_row['id'];
    }

    // Set user as inactive by default (use 'active' column, not 'is_active')
    $active = 0;

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Use email as username
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id, active) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$email, $email, $hashed_password, $role_id, $active])) {
            // Send email with email as username
            $to = $email;
            $subject = "Your School CRM Account Registration";
            $message = "Hello,\n\n"
                . "Thank you for registering at School CRM.\n"
                . "Your account details:\n"
                . "Username (Email): $email\n"
                . "Password: (hidden for your security)\n\n"
                . "Please wait for an administrator to activate your account.\n\n"
                . "If you did not register, please ignore this email.";
            $headers = "From: no-reply@school-crm.com\r\n";
            @mail($to, $subject, $message, $headers);

            $_SESSION['register_success'] = true;
            header("Location: register.php");
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
        /* Adugna Gizaw: Social registration button styles, adugna- prefix for branding and patent. */
        .adugna-social-register { margin-bottom: 18px; }
        .adugna-social-btn {
            border: 1px solid #e0e0e0;
            background: #fff;
            color: #222;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 500;
            padding: 7px 16px;
            margin: 0 2px 6px 2px;
            transition: box-shadow 0.13s, border 0.13s;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
            min-width: 44px;
        }
        .adugna-social-btn:hover, .adugna-social-btn:focus {
            box-shadow: 0 2px 8px rgba(44,62,80,0.09);
            border: 1.5px solid #3498db;
            text-decoration: none;
        }
        .adugna-google .adugna-social-label { color: #ea4335; }
        .adugna-telegram .adugna-social-label { color: #229ed9; }
        .adugna-facebook .adugna-social-label { color: #1877f3; }
        @media (max-width: 600px) {
            .adugna-social-btn { font-size: 0.97em; padding: 7px 8px; }
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
        <?php
        // Show banner if exists
        $bannerPath = 'uploads/banner.png';
        if (file_exists(__DIR__ . '/uploads/banner.png')): ?>
            <div style="text-align:center;margin-bottom:12px;">
                <img src="<?= $bannerPath ?>?v=<?= filemtime(__DIR__ . '/uploads/banner.png') ?>" alt="Site Banner" style="max-width:100%;max-height:80px;border-radius:8px;">
            </div>
        <?php endif; ?>
        <?php if (!empty($settings['site_name'])): ?>
            <div style="text-align:center;font-size:1.25em;font-weight:700;color:#3498db;margin-bottom:8px;">
                <?= htmlspecialchars($settings['site_name']) ?>
            </div>
        <?php endif; ?>
        <div class="register-logo">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="register-title">Create Account</div>
        <!-- Adugna Gizaw: Social registration options for Gmail, Telegram, Facebook. -->
        <?php if (!empty($errors['general'])): ?>
            <div class="error-message"><?= htmlspecialchars($errors['general']) ?></div>
        <?php elseif (!empty($_SESSION['register_success'])): ?>
            <div class="success-message">
                Registration successful!<br>
                <strong>Next steps:</strong><br>
                Your account has been created but is not yet active.<br>
                An administrator will review and activate your account.<br>
                You will not be able to log in until your account is approved.<br>
                Please check your email for updates or contact support if you have questions.
            </div>
            <?php unset($_SESSION['register_success']); ?>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <!-- Remove Username field, only show Email -->
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

<?php require_once __DIR__ . '/footer.php'; ?>
</body>
</html>
