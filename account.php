<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session before any output
session_start();

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '..includes/db.php';

// Verify user is logged in
requireLogin();

// Check session user data exists
if (!isset($_SESSION['user_id'])) {
    die("Session error: User not authenticated");
}

$user_id = $_SESSION['user_id'];

// Database connection check
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            // Profile update logic
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([
                $_POST['username'],
                $_POST['email'],
                $user_id
            ]);
            // Update session data
            $_SESSION['user']['username'] = $_POST['username'];
            $_SESSION['user']['email'] = $_POST['email'];
            $_SESSION['success'] = "Profile updated successfully!";
        }

        if (isset($_POST['change_password'])) {
            // Password change logic
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!password_verify($_POST['current_password'], $user['password'])) {
                $_SESSION['error'] = "Current password is incorrect!";
            } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                $_SESSION['error'] = "New passwords don't match!";
            } else {
                $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([$newHash, $user_id]);
                $_SESSION['success'] = "Password changed successfully!";
            }
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Fetch updated user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("User not found in database");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Verify header file exists
$headerFile = '../includes/header.php';
if (!file_exists($headerFile)) {
    die("Missing header file: $headerFile");
}
?>

<!-- HTML START -->
<?php include $headerFile; ?>

<div class="account-container">
    <!-- Display session messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <h1>Account Management</h1>
    
    <div class="form-section">
        <h2>Profile Information</h2>
        <form method="POST">
            <div class="form-group">
                <label>Avatar:</label>
                <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.jpg') ?>" 
                     class="avatar-preview">
                <input type="file" name="avatar">
            </div>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" 
                       value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" 
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            
            <button type="submit" name="update_profile" class="btn btn-primary">
                Update Profile
            </button>
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
            
            <button type="submit" name="change_password" class="btn btn-warning">
                Change Password
            </button>
        </form>
    </div>

    <div class="form-section">
        <h2>Login Security</h2>
        <p>Last Login: 
            <?= isset($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never' ?>
        </p>
        <a href="activity_log.php" class="btn btn-secondary">View Activity Log</a>
    </div>
</div>

<?php 
// Verify footer file exists
$footerFile = '../includes/footer.php';
if (!file_exists($footerFile)) {
    die("Missing footer file: $footerFile");
}
include $footerFile; 
?>