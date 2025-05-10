<?php
// Start session at the very top for session reliability
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Set timezone for user edit page
date_default_timezone_set('Africa/Nairobi');
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}
$pageTitle = 'Edit User';

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found!";
    header("Location: users.php");
    exit();
}

// Fetch all roles for the form select
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

// Fetch email settings from system_settings table
$email_settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_group = 'email'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $email_settings[$row['setting_key']] = $row['setting_value'];
}
$from_email = $email_settings['from_email'] ?? $email_settings['smtp_user'] ?? 'noreply@example.com';
$from_name = $settings['site_name'] ?? 'School CRM';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = $_POST['role_id'];
    $active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
    $validation_error = '';

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validation_error = 'Please enter a valid email address.';
    }
    // Validate username (optional: add more rules if needed)
    elseif (empty($username)) {
        $validation_error = 'Username cannot be empty.';
    }

    // Check if username or email already exists (excluding current user)
    if (!$validation_error) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $validation_error = 'Username or email already exists!';
        }
    }

    if ($validation_error) {
        $_SESSION['error'] = $validation_error;
        // Do not redirect here, let the form reload and display the error message
    } else {
        // Check if account is being activated now
        $was_inactive = ($user['active'] == 0);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role_id = ?, active = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role_id, $active, $id]);
        // If user was inactive and now is active, send activation email
        if ($was_inactive && $active == 1) {
            // Fetch latest first and last name for a personalized greeting
            $stmt_name = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $stmt_name->execute([$id]);
            $name_row = $stmt_name->fetch(PDO::FETCH_ASSOC);
            $first_name = $name_row['first_name'] ?? '';
            $last_name = $name_row['last_name'] ?? '';
            $to = $email;
            $subject = "Your School CRM Account Has Been Activated";
            // Professional: Use full name in greeting for clarity and personalization
            $body = "Hello " . trim($first_name . ' ' . $last_name) . ",\n\n"
                . "Congratulations! Your School CRM account has been activated by an administrator.\n\n"
                . "You can now log in using your email and password at: " . (isset($settings['site_url']) ? $settings['site_url'] : 'the login page') . "\n\n"
                . "If you have any questions, please contact support.\n\n"
                . "Thank you,\nSchool CRM Team";
            $headers = "From: $from_name <$from_email>\r\n";
            // Professional: Use mail() for notification, consider using a robust mailer in production
            @mail($to, $subject, $body, $headers);
        }
        $_SESSION['success'] = "User updated successfully!";
        // Instead of redirecting, reload the page to show the success message
        header("Location: edit_user.php?id=" . urlencode($id));
        exit();
    }
}

// Handle password reset (random)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_random_password'])) {
    $new_password = bin2hex(random_bytes(4)) . rand(100,999); // 8+ chars, secure random
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $id]);
    // Fetch latest first and last name for email greeting
    $stmt_name = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt_name->execute([$id]);
    $name_row = $stmt_name->fetch(PDO::FETCH_ASSOC);
    $first_name = $name_row['first_name'] ?? '';
    $last_name = $name_row['last_name'] ?? '';
    // Professional: Send password reset email with full name
    $to = $user['email'];
    $subject = "Your password has been reset";
    $body = "Hello " . trim($first_name . ' ' . $last_name) . ",\n\nYour account password has been reset by an administrator.\n\nTemporary Password: $new_password\n\nPlease log in using this password and change it immediately for your security.\n\nIf you did not request this change, please contact support immediately.\n\nThank you,\nSchool CRM Team";
    $headers = "From: $from_name <$from_email>\r\n";
    @mail($to, $subject, $body, $headers);
    $_SESSION['success'] = "Password reset and sent to user's email.";
    header("Location: edit_user.php?id=" . urlencode($id));
    exit();
}

// Handle password reset (manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_manual_password'])) {
    $manual_password = $_POST['manual_password'] ?? '';
    if (strlen($manual_password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($manual_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $id]);
        // Fetch latest first and last name
        $stmt_name = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt_name->execute([$id]);
        $name_row = $stmt_name->fetch(PDO::FETCH_ASSOC);
        $first_name = $name_row['first_name'] ?? '';
        $last_name = $name_row['last_name'] ?? '';
        // Optionally send email for manual reset (uncomment if needed)
        /*
        $to = $user['email'];
        $subject = "Your password has been reset";
        $body = "Hello " . trim($first_name . ' ' . $last_name) . ",\n\nYour account password has been reset by an administrator.\n\nTemporary Password: $manual_password\n\nPlease log in using this password and change it immediately for your security.\n\nIf you did not request this change, please contact support immediately.\n\nThank you,\nSchool CRM Team";
        $headers = "From: $from_name <$from_email>\r\n";
        @mail($to, $subject, $body, $headers);
        */
        $_SESSION['success'] = "Password reset successfully.";
        header("Location: edit_user.php?id=" . urlencode($id));
        exit();
    }
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "User deleted successfully.";
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 600px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 22px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.25em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .adugna-form-group {
            margin-bottom: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.2em;
        }
        .adugna-form-group label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input,
        .adugna-form-group select {
            padding: 7px 10px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
            border: 1px solid #e74c3c;
        }
        .adugna-btn-danger:hover { background: #c82333; }
        .adugna-btn-info {
            background: #2563eb;
            color: #fff;
            border: 1px solid #2563eb;
        }
        .adugna-btn-info:hover { background: #1741a6; }
        .adugna-error-message {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        .adugna-success-message {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        hr { margin: 2rem 0; border: none; border-top: 1px solid #e5e7eb; }
        @media (max-width: 900px) {
            .adugna-main-content { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-btn, .adugna-btn-primary { padding: 6px 10px; font-size: 0.95em; }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>
    <script src="../assets/js/edit_user_validation.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-user-edit"></i> <?= htmlspecialchars($pageTitle) ?>
            </div>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="adugna-error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="adugna-success-message">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="update_user">
                <div class="adugna-form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="adugna-form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="adugna-form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id']; ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adugna-form-group">
                    <label for="active">Account Status:</label>
                    <select id="active" name="active">
                        <option value="1" <?= ($user['active'] == 1) ? 'selected' : '' ?>>Activate</option>
                        <option value="0" <?= ($user['active'] == 0) ? 'selected' : '' ?>>Deactivate</option>
                    </select>
                </div>
                <div class="adugna-form-actions">
                    <a href="users.php" class="adugna-btn adugna-btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button type="submit" class="adugna-btn"><i class="fas fa-save"></i> Update User</button>
                </div>
            </form>
            <hr>
            <h3 style="color:#215967;">Password Management</h3>
            <form method="POST" style="margin-bottom:1.2rem;">
                <button type="submit" name="reset_random_password" class="adugna-btn adugna-btn-info" onclick="return confirm('Reset password and send to user email?')">
                    <i class="fas fa-random"></i> Reset Random Password & Email
                </button>
            </form>
            <form method="POST" style="display:flex;gap:1rem;align-items:center;">
                <input type="password" name="manual_password" placeholder="Enter new password" required style="flex:1;min-width:180px;">
                <button type="submit" name="reset_manual_password" class="adugna-btn">
                    <i class="fas fa-key"></i> Set Password Manually
                </button>
            </form>
            <hr>
            <h3 style="color:#e74c3c;">Danger Zone</h3>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone!');">
                <button type="submit" name="delete_user" class="adugna-btn adugna-btn-danger">
                    <i class="fas fa-trash"></i> Delete User
                </button>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
