<?php
// Error reporting - consider logging to file in production
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Include required files
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch the current user
function getCurrentUser() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$user = getCurrentUser();
if (!$user) {
    $_SESSION['error'] = "User session expired. Please login again.";
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid form submission. Please try again.";
        header("Location: profile.php");
        exit();
    }

    // Handle profile update
    if (isset($_POST['update_profile'])) {
        handleProfileUpdate($pdo, $user);
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        handleChangePassword($pdo, $user);
    }
}

function handleProfileUpdate($pdo, $user) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $_SESSION['error'] = "Username must be 3-30 characters (letters, numbers, underscores only).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } else {
        $avatar = handleAvatarUpload($user);
        if ($avatar !== false) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $avatar, $_SESSION['user_id']])) {
                $_SESSION['success'] = "Profile updated successfully!";
                header("Location: profile.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to update profile.";
            }
        }
    }
}

function handleAvatarUpload($user) {
    $avatar = $user['avatar'] ?? 'default.jpg';
    if (!empty($_FILES['avatar']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['avatar']['type'];
        $fileSize = $_FILES['avatar']['size'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            $fileInfo = getimagesize($_FILES['avatar']['tmp_name']);
            if (!$fileInfo || !in_array($fileInfo['mime'], $allowedTypes)) {
                $_SESSION['error'] = "Only JPG, PNG, and GIF files are allowed.";
                return false;
            }
        } else {
            $_SESSION['error'] = "No file uploaded or upload error.";
            return false;
        }

        if ($fileSize > $maxSize) {
            $_SESSION['error'] = "File size must be less than 2MB.";
            return false;
        } else {
            $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $fileName = 'avatar_' . $_SESSION['user_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                if (!empty($user['avatar']) && $user['avatar'] !== 'default.jpg' && file_exists($uploadDir . $user['avatar'])) {
                    unlink($uploadDir . $user['avatar']);
                }
                return $fileName;
            } else {
                $_SESSION['error'] = "Error uploading your file.";
                return false;
            }
        }
    }
    return $avatar;
}

function handleChangePassword($pdo, $user) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $dbPassword = $stmt->fetchColumn();

    if (!password_verify($currentPassword, $dbPassword)) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "New passwords do not match.";
    } elseif (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        $_SESSION['error'] = "Password must be at least 8 characters with at least one number and one uppercase letter.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Password changed successfully!";
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to change password.";
        }
    }
}

?>

<?php include_once 'includes/admin_sidebar.php'; ?>

<div class="main-content-container">
    <div class="profile-main-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.jpg') ?>" 
                     alt="Profile Picture"
                     onerror="this.onerror=null; this.src='../uploads/avatars/default.jpg';">
            </div>
            <div class="profile-info">
                <h3><?= htmlspecialchars($user['username'] ?? 'Unknown') ?></h3>
                <div class="card-text">Email: <?= htmlspecialchars($user['email'] ?? 'No email provided') ?></div>
                <span class="badge bg-primary">Role: <?= htmlspecialchars($user['role_name'] ?? 'Unknown Role') ?></span>
                <div class="text-muted mt-2">Last Login: <?= !empty($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never' ?></div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="profile-forms-row">
            <div class="profile-form-card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" id="username" name="username" 
                                   class="erpnext-input form-control form-control-sm"
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                   required
                                   pattern="[a-zA-Z0-9_]{3,30}"
                                   title="3-30 characters (letters, numbers, underscores)">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" 
                                   class="erpnext-input form-control form-control-sm"
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Profile Picture:</label>
                            <input type="file" id="avatar" name="avatar" 
                                   class="erpnext-input form-control form-control-sm"
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="form-text text-muted">Max 2MB (JPG, PNG, GIF only)</small>
                        </div>
                        <button type="submit" class="erpnext-btn btn-primary btn-sm w-100">Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="profile-form-card">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password:</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="erpnext-input form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password:</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="erpnext-input form-control form-control-sm"
                                   required
                                   pattern="(?=.*\d)(?=.*[A-Z]).{8,}"
                                   title="Must contain at least one number, one uppercase letter, and be at least 8 characters">
                            <small class="form-text text-muted">Minimum 8 characters with at least one number and uppercase letter</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="erpnext-input form-control form-control-sm" required>
                        </div>
                        <button type="submit" class="erpnext-btn btn-secondary btn-sm w-100">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>