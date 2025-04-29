<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

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

// Fetch role name for user (fixes "Unknown Role")
// Fix: Use correct column name from db.sql (likely 'role_name' only)
function getRoleName($role_id, $pdo) {
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $role = $stmt->fetchColumn();
    return $role ?: 'Unknown Role';
}
$user['role_name'] = isset($user['role_id']) ? getRoleName($user['role_id'], $pdo) : 'Unknown Role';

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/css/brands.min.css">
    <link rel="stylesheet" href="../assets/css/solid.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script></div>
<!-- Consistent layout and sidebar/footer styling as in admin/dashboard.php -->
<div class="admin-dashboard">
    <?php 
    // Use the same sidebar include as dashboard.php
    include __DIR__ . '/includes/admin_sidebar.php'; 
    ?>
    <div class="admin-main">
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
                        <span class="adugna-badge">Role: <?= htmlspecialchars($user['role_name']) ?></span>
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

                <div class="adugna-profile-forms-row">
                    <div class="adugna-profile-form-card adugna-card">
                        <div class="adugna-card-header">
                            <i class="fas fa-user-edit" style="font-size:1.1em;color:#2e86c1;"></i>
                            <span>Profile Information</span>
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
                                    <small class="adugna-form-text">Max 2MB (JPG, PNG, GIF only)</small>
                                </div>
                                <button type="submit" class="adugna-btn adugna-btn-primary btn-sm w-100">
                                    <i class="fas fa-save" style="font-size:0.95em;margin-right:5px;"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="adugna-profile-form-card adugna-card">
                        <div class="adugna-card-header bg-secondary">
                            <i class="fas fa-key" style="font-size:1.1em;color:#34495e;"></i>
                            <span>Change Password</span>
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
                                    <small class="adugna-form-text">Minimum 8 characters with at least one number and uppercase letter</small>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="adugna-form-label form-label">Confirm New Password:</label>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="adugna-input form-control form-control-sm" required>
                                </div>
                                <button type="submit" class="adugna-btn adugna-btn-secondary btn-sm w-100">
                                    <i class="fas fa-sync-alt" style="font-size:0.95em;margin-right:5px;"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php 
        // Use the same footer include as dashboard.php
        include __DIR__ . '/includes/footer.php'; 
        ?>
    </div>
</div>

<!-- Adugna ERPNext Custom Styles -->
<style>
/* --- Begin: Sidebar & Main Layout Styles (from dashboard.php) --- */
.admin-dashboard {
    display: flex;
    min-height: 100vh;
    background: #f4f6fa;
}
.admin-main {
    flex: 1;
    padding: 2rem 2.5rem;
}
@media (max-width: 900px) {
    .admin-main {
        padding: 1rem 0.5rem;
    }
}
@media (max-width: 600px) {
    .admin-main {
        padding: 10px 2px 80px;
    }
}
/* --- End: Sidebar & Main Layout Styles --- */

/* --- Begin: Profile Page Styles (existing) --- */
/* Adugna ERPNext Card Styling */
.adugna-card {
    border-radius: 10px;
    box-shadow: 0 4px 24px rgba(44,62,80,0.09), 0 1.5px 4px rgba(44,62,80,0.04);
    border: 1px solid #e2e2e2;
    background: linear-gradient(135deg, #f8fafc 0%, #f4f6fa 100%);
    margin-bottom: 28px;
    padding: 0;
    transition: box-shadow 0.2s, transform 0.2s;
}
.adugna-card:hover {
    box-shadow: 0 8px 32px rgba(44,62,80,0.13), 0 2px 8px rgba(44,62,80,0.07);
    transform: translateY(-2px) scale(1.01);
}
.adugna-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e2e2;
    padding: 14px 22px;
    font-size: 1.07rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    color: #2e86c1;
    display: flex;
    align-items: center;
    gap: 8px;
}
.adugna-card-header.bg-secondary {
    background: #eaf2fb;
    color: #34495e;
}
.adugna-card-body {
    padding: 22px 22px 18px 22px;
    background: transparent;
}
.adugna-profile-avatar img {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    border: 2px solid #e2e2e2;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(44,62,80,0.07);
    background: #fff;
    transition: box-shadow 0.2s;
}
.adugna-profile-avatar img:hover {
    box-shadow: 0 4px 16px rgba(44,62,80,0.13);
}
.adugna-btn {
    font-size: 0.90rem;
    padding: 0.32rem 1rem;
    border-radius: 4px;
    border: none;
    transition: background 0.2s, box-shadow 0.2s;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(44,62,80,0.04);
}
.adugna-btn-primary {
    background: linear-gradient(90deg, #2e86c1 60%, #3498db 100%);
    color: #fff;
}
.adugna-btn-primary:hover {
    background: #1b4f72;
    color: #fff;
    box-shadow: 0 2px 8px rgba(44,62,80,0.09);
}
.adugna-btn-secondary {
    background: #f4f6f7;
    color: #2e86c1;
    border: 1px solid #d6dbdf;
}
.adugna-btn-secondary:hover {
    background: #d6dbdf;
    color: #1b4f72;
}
.adugna-form-label {
    font-size: 0.93rem;
    font-weight: 600;
    color: #2e86c1;
    letter-spacing: 0.01em;
}
.adugna-input {
    font-size: 0.91rem;
    padding: 0.32rem 0.7rem;
    border-radius: 4px;
    border: 1px solid #d6dbdf;
    background: #f8fafc;
    transition: border 0.18s;
}
.adugna-input:focus {
    border: 1.5px solid #2e86c1;
    outline: none;
    background: #fff;
}
.adugna-badge {
    font-size: 0.82rem;
    padding: 0.18em 0.65em;
    border-radius: 12px;
    background: #eaf2fb;
    color: #2e86c1;
    margin-left: 0.5em;
    font-weight: 600;
    letter-spacing: 0.01em;
}
.adugna-profile-header {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 18px;
    background: linear-gradient(90deg, #eaf2fb 60%, #f8fafc 100%);
    border-radius: 8px;
    padding: 18px 22px;
    box-shadow: 0 1.5px 4px rgba(44,62,80,0.04);
}
.adugna-profile-info h3 {
    font-size: 1.13rem;
    margin-bottom: 0.2em;
    color: #2e86c1;
    font-weight: 700;
}
.adugna-profile-info .card-text {
    font-size: 0.97rem;
    color: #34495e;
}
.adugna-profile-info .text-muted {
    font-size: 0.91rem;
    color: #7f8c8d !important;
}
.adugna-profile-forms-row {
    display: flex;
    gap: 28px;
    flex-wrap: wrap;
    margin-top: 10px;
}
.adugna-profile-form-card {
    flex: 1 1 320px;
    min-width: 320px;
    max-width: 420px;
    transition: box-shadow 0.2s;
}
.adugna-profile-form-card:hover {
    box-shadow: 0 6px 24px rgba(44,62,80,0.11);
}
.adugna-form-text {
    font-size: 0.83rem;
    color: #7f8c8d;
}
.alert {
    border-radius: 6px;
    font-size: 0.96rem;
    padding: 0.7em 1.2em;
    margin-bottom: 1.1em;
    box-shadow: 0 1px 4px rgba(44,62,80,0.07);
}
.btn-close {
    font-size: 0.8rem !important;
}
@media (max-width: 900px) {
    .adugna-profile-forms-row {
        flex-direction: column;
        gap: 18px;
    }
    .adugna-profile-form-card {
        max-width: 100%;
    }
    .adugna-profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 14px 10px;
    }
}
@media (max-width: 600px) {
    .adugna-profile-header {
        padding: 10px 4px;
    }
    .adugna-card-body {
        padding: 12px 6px 10px 6px;
    }
    .adugna-profile-form-card {
        min-width: 0;
    }
}
/* --- End: Profile Page Styles --- */
</style>