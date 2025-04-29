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

<!-- Adugna ERPNext Custom Styles -->
<style>
/* Adugna ERPNext Card Styling */
.adugna-card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #e2e2e2;
    background: #fff;
    margin-bottom: 24px;
    padding: 0;
}
.adugna-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e2e2;
    padding: 12px 18px;
    font-size: 1rem;
    font-weight: 600;
}
.adugna-card-body {
    padding: 18px;
}
.adugna-profile-avatar img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 2px solid #e2e2e2;
    object-fit: cover;
}
.adugna-btn {
    font-size: 0.92rem;
    padding: 0.35rem 1.1rem;
    border-radius: 4px;
    border: none;
    transition: background 0.2s;
}
.adugna-btn-primary {
    background: #2e86c1;
    color: #fff;
}
.adugna-btn-primary:hover {
    background: #1b4f72;
}
.adugna-btn-secondary {
    background: #f4f6f7;
    color: #2e86c1;
    border: 1px solid #d6dbdf;
}
.adugna-btn-secondary:hover {
    background: #d6dbdf;
}
.adugna-form-label {
    font-size: 0.95rem;
    font-weight: 500;
}
.adugna-input {
    font-size: 0.92rem;
    padding: 0.35rem 0.7rem;
    border-radius: 4px;
    border: 1px solid #d6dbdf;
}
.adugna-badge {
    font-size: 0.85rem;
    padding: 0.2em 0.7em;
    border-radius: 12px;
    background: #eaf2fb;
    color: #2e86c1;
    margin-left: 0.5em;
}
.adugna-profile-header {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 18px;
}
.adugna-profile-info h3 {
    font-size: 1.15rem;
    margin-bottom: 0.2em;
}
.adugna-profile-forms-row {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}
.adugna-profile-form-card {
    flex: 1 1 320px;
    min-width: 320px;
    max-width: 420px;
}
@media (max-width: 900px) {
    .adugna-profile-forms-row {
        flex-direction: column;
        gap: 18px;
    }
    .adugna-profile-form-card {
        max-width: 100%;
    }
}
</style>

<div class="main-content-container">
    <div class="profile-main-container">
        <div class="adugna-profile-header">
            <div class="adugna-profile-avatar">
                <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.jpg') ?>" 
                     alt="Profile Picture"
                     onerror="this.onerror=null; this.src='../uploads/avatars/default.jpg';">
            </div>
            <div class="adugna-profile-info">
                <h3><?= htmlspecialchars($user['username'] ?? 'Unknown') ?></h3>
                <div class="card-text">Email: <?= htmlspecialchars($user['email'] ?? 'No email provided') ?></div>
                <span class="adugna-badge">Role: <?= htmlspecialchars($user['role_name'] ?? 'Unknown Role') ?></span>
                <div class="text-muted mt-2" style="font-size:0.92rem;">Last Login: <?= !empty($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never' ?></div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size:0.95rem;">
                <?= htmlspecialchars($_SESSION['success'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.8rem;"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size:0.95rem;">
                <?= htmlspecialchars($_SESSION['error'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.8rem;"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="adugna-profile-forms-row">
            <div class="adugna-profile-form-card adugna-card">
                <div class="adugna-card-header">
                    <h5 class="mb-0" style="font-size:1rem;">Profile Information</h5>
                </div>
                <div class="adugna-card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="username" class="adugna-form-label form-label">Username:</label>
                            <input type="text" id="username" name="username" 
                                   class="adugna-input form-control form-control-sm"
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                   required
                                   pattern="[a-zA-Z0-9_]{3,30}"
                                   title="3-30 characters (letters, numbers, underscores)">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="adugna-form-label form-label">Email:</label>
                            <input type="email" id="email" name="email" 
                                   class="adugna-input form-control form-control-sm"
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="adugna-form-label form-label">Profile Picture:</label>
                            <input type="file" id="avatar" name="avatar" 
                                   class="adugna-input form-control form-control-sm"
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="form-text text-muted" style="font-size:0.85rem;">Max 2MB (JPG, PNG, GIF only)</small>
                        </div>
                        <button type="submit" class="adugna-btn adugna-btn-primary btn-sm w-100">Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="adugna-profile-form-card adugna-card">
                <div class="adugna-card-header bg-secondary">
                    <h5 class="mb-0" style="font-size:1rem;">Change Password</h5>
                </div>
                <div class="adugna-card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="current_password" class="adugna-form-label form-label">Current Password:</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="adugna-input form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="adugna-form-label form-label">New Password:</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="adugna-input form-control form-control-sm"
                                   required
                                   pattern="(?=.*\d)(?=.*[A-Z]).{8,}"
                                   title="Must contain at least one number, one uppercase letter, and be at least 8 characters">
                            <small class="form-text text-muted" style="font-size:0.85rem;">Minimum 8 characters with at least one number and uppercase letter</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="adugna-form-label form-label">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="adugna-input form-control form-control-sm" required>
                        </div>
                        <button type="submit" class="adugna-btn adugna-btn-secondary btn-sm w-100">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>