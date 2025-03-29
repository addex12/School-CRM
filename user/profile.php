<?php
// Start output buffering at the very top
ob_start();

// Start session and error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Get current user data
$user = getCurrentUser();
if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: ../login.php");
    ob_end_flush();
    exit();
}

$userId = $user['id'];
$isAdmin = ($user['role_name'] === 'admin');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format";
        } else {
            // Check if email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Email already in use by another account";
            } else {
                // Handle file upload
                $avatar = $user['avatar'] ?? 'default.jpg';
                if (!empty($_FILES['avatar']['name'])) {
                    $uploadDir = __DIR__ . '/../uploads/avatars/';
                    if (!file_exists($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            $_SESSION['error'] = "Failed to create upload directory";
                            header("Location: profile.php");
                            ob_end_flush();
                            exit();
                        }
                    }
                    
                    // Validate file type and size
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['avatar']['type'];
                    $maxSize = 2 * 1024 * 1024; // 2MB
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        $_SESSION['error'] = "Only JPG, PNG, and GIF files are allowed.";
                    } elseif ($_FILES['avatar']['size'] > $maxSize) {
                        $_SESSION['error'] = "File size must be less than 2MB";
                    } else {
                        $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                        $fileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
                        $targetFile = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                            // Delete old avatar if not default
                            if (!empty($user['avatar']) && $user['avatar'] !== 'default.jpg' && file_exists($uploadDir . $user['avatar'])) {
                                @unlink($uploadDir . $user['avatar']);
                            }
                            $avatar = $fileName;
                        } else {
                            $_SESSION['error'] = "Sorry, there was an error uploading your file.";
                        }
                    }
                }
                
                // Update user details
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?");
                if ($stmt->execute([$username, $email, $avatar, $userId])) {
                    $_SESSION['success'] = "Profile updated successfully!";
                    $_SESSION['username'] = $username;
                    header("Location: profile.php");
                    ob_end_flush();
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to update profile.";
                }
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $dbPassword = $stmt->fetchColumn();
        
        if (!password_verify($currentPassword, $dbPassword)) {
            $_SESSION['error'] = "Current password is incorrect";
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "New passwords do not match";
        } elseif (strlen($newPassword) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $userId])) {
                $_SESSION['success'] = "Password changed successfully!";
                header("Location: profile.php");
                ob_end_flush();
                exit();
            } else {
                $_SESSION['error'] = "Failed to change password";
            }
        }
    }
}

// Include header after all processing is done
if (file_exists(__DIR__ . '/../includes/header.php')) {
    include __DIR__ . '/../includes/header.php';
}
?>

<div class="profile-container">
    <h1>Manage Your Profile</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="profile-section">
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.jpg') ?>" 
                     alt="Profile Picture" class="avatar-img">
            </div>
            
            <div class="profile-info">
                <h3><?= htmlspecialchars($user['username'] ?? 'Unknown') ?></h3>
                <p><?= htmlspecialchars($user['email'] ?? 'No email provided') ?></p>
                <p class="role-badge"><?= htmlspecialchars($user['role_name'] ?? 'Unknown Role') ?></p>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="profile-card">
                <h2>Profile Information</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" 
                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="avatar">Profile Picture:</label>
                        <input type="file" id="avatar" name="avatar" accept="image/*">
                        <small class="form-text">Max 2MB (JPG, PNG, GIF)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
            
            <div class="profile-card">
                <h2>Change Password</h2>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small class="form-text">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
            
            <?php if (!$isAdmin): ?>
            <div class="profile-card">
                <h2>Account Actions</h2>
                <button class="btn btn-danger" onclick="confirmDeactivate()">Deactivate Account</button>
                <small class="form-text">This will disable your account but preserve your data</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmDeactivate() {
    if (confirm('Are you sure you want to deactivate your account? You can reactivate it by logging in again.')) {
        window.location.href = 'deactivate.php';
    }
}
</script>

<style>
/* Your existing CSS styles remain exactly the same */
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.profile-section {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

/* ... rest of your CSS ... */
</style>

<?php include __DIR__ . '/../includes/footer.php'; 
ob_end_flush(); // End output buffering and flush all output
?>