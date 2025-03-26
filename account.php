<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([
            $_POST['username'],
            $_POST['email'],
            $user_id
        ]);
        $_SESSION['success'] = "Profile updated successfully!";
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        if (password_verify($_POST['current_password'], $_SESSION['user']['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newHash, $user_id]);
                $_SESSION['success'] = "Password changed successfully!";
            } else {
                $_SESSION['error'] = "New passwords don't match!";
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect!";
        }
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .account-container { max-width: 800px; margin: 20px auto; }
        .form-section { margin-bottom: 30px; padding: 20px; background: #f8f9fa; }
        .avatar-preview { width: 150px; height: 150px; border-radius: 50%; }
    </style>
</head>
<body>
    <?php include 'includes/user_header.php'; ?>
    
    <div class="account-container">
        <h1>Account Management</h1>
        
        <div class="form-section">
            <h2>Profile Information</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Avatar:</label>
                    <img src="../uploads/avatars/<?= $user['avatar'] ?>" class="avatar-preview">
                    <input type="file" name="avatar">
                </div>
                
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </div>

        <div class="form-section">
            <h2>Change Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password:</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>New Password:</label>
                    <input type="password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password:</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
            </form>
        </div>

        <div class="form-section">
            <h2>Login Security</h2>
            <p>Last Login: <?= date('M j, Y g:i a', strtotime($user['last_login'])) ?></p>
            <a href="activity_log.php" class="btn btn-secondary">View Activity Log</a>
        </div>
    </div>
</body>
</html>