<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = $_POST['role_id'];
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
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role_id, $id]);
        $_SESSION['success'] = "User updated successfully!";
        header("Location: users.php");
        exit();
    }
}

// Handle password reset (random)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_random_password'])) {
    $new_password = bin2hex(random_bytes(4)) . rand(100,999); // 8+ chars
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $id]);
    // Send email
    $to = $user['email'];
    $subject = "Your password has been reset";
    $body = "Hello " . $user['username'] . ",\n\nYour new password is: $new_password\n\nPlease login and change it.";
    @mail($to, $subject, $body);
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
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2.2rem 2rem 2.5rem 2rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #215967; }
        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
        }
        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        .erpnext-btn, .btn, .btn-primary, .btn-secondary {
            display: inline-block;
            padding: 10px 22px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #215967;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary, .erpnext-btn.btn-primary {
            background: #3b82f6;
            color: #fff;
        }
        .btn-primary:hover, .erpnext-btn.btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary, .erpnext-btn.btn-secondary {
            background: #eaeaea;
            color: #666;
        }
        .btn-secondary:hover, .erpnext-btn.btn-secondary:hover {
            background: #e2efda;
            color: #215967;
        }
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            border: 1px solid #fca5a5;
        }
        @media (max-width: 900px) {
            .form-container, .admin-main { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .form-container, .admin-main { padding: 4px; }
            .erpnext-btn, .btn, .btn-primary { padding: 6px 10px; font-size: 0.95em; }
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
        <div class="admin-main">
            <header class="admin-header">
                <h1 style="color:#215967;font-weight:700;"><i class="fas fa-user-edit"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="form-container">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" style="background:#dcfce7;color:#27ae60;padding:1rem;margin-bottom:1rem;border-radius:6px;">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="update_user">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id']; ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <a href="users.php" class="erpnext-btn btn-secondary">Cancel</a>
                        <button type="submit" class="erpnext-btn btn-primary"><i class="fas fa-save"></i> Update User</button>
                    </div>
                </form>
                <hr style="margin:2rem 0;">
                <h3 style="color:#215967;">Password Management</h3>
                <form method="POST" style="margin-bottom:1.2rem;">
                    <button type="submit" name="reset_random_password" class="erpnext-btn btn-info" onclick="return confirm('Reset password and send to user email?')">
                        <i class="fas fa-random"></i> Reset Random Password & Email
                    </button>
                </form>
                <form method="POST" style="display:flex;gap:1rem;align-items:center;">
                    <input type="password" name="manual_password" placeholder="Enter new password" required style="flex:1;min-width:180px;">
                    <button type="submit" name="reset_manual_password" class="erpnext-btn btn-primary">
                        <i class="fas fa-key"></i> Set Password Manually
                    </button>
                </form>
                <hr style="margin:2rem 0;">
                <h3 style="color:#e74c3c;">Danger Zone</h3>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone!');">
                    <button type="submit" name="delete_user" class="erpnext-btn btn-danger">
                        <i class="fas fa-trash"></i> Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>