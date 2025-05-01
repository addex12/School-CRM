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
function getCurrentUser() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
        header("Location: ../login.php");
        exit();
    }
}
// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch the current user and all columns
$user = getCurrentUser();
if (!$user) {
    $_SESSION['error'] = "User session expired. Please login again.";
    header("Location: ../login.php");
    exit();
}

// Fetch role name (never show 'Unknown')
$roleName = '';
if (!empty($user['role_id'])) {
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt->execute([$user['role_id']]);
    $roleName = $stmt->fetchColumn() ?: '';
}
$user['role_name'] = $roleName;

// Fetch extra fields from relevant role table
$extraFields = [];
$roleTable = '';
$roleKey = '';
if ($roleName === 'teacher') {
    $roleTable = 'teachers'; $roleKey = 'user_id';
} elseif ($roleName === 'student') {
    $roleTable = 'students'; $roleKey = 'user_id';
} elseif ($roleName === 'parent') {
    $roleTable = 'parents'; $roleKey = 'user_id';
}
if ($roleTable) {
    $stmt = $pdo->prepare("SELECT * FROM $roleTable WHERE $roleKey = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $extraFields = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Get all user table columns except id, password, role_id, avatar, username, email, created_at, last_active, last_login, online, active, tracking_token, remember_token
$userColumns = array_diff(array_keys($user), ['id','password','role_id','avatar','username','email','created_at','last_active','last_login','online','active','tracking_token','remember_token','role_name']);
// Get all extra fields except id, user_id, created_at, status
$extraColumns = $extraFields ? array_diff(array_keys($extraFields), ['id','user_id','created_at','status']) : [];

// Define user ID
$userId = $_SESSION['user_id'];

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
        handleProfileUpdate($pdo, $user, $userId);
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        handleChangePassword($pdo, $user, $userId);
    }
}

// Function to handle profile updates
function handleProfileUpdate($pdo, $user, $userId) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $userFields = $_POST['user_fields'] ?? [];
    $extraFields = $_POST['extra_fields'] ?? [];

    // Validate username
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $_SESSION['error'] = "Username must be 3-30 characters (letters, numbers, underscores only).";
    } 
    // Validate email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } else {
        // Check if email is already in use
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email is already in use by another account.";
        } else {
            $avatar = handleAvatarUpload($user, $userId);
            if ($avatar !== false) {
                // Update user details
                $set = 'username = ?, email = ?, avatar = ?';
                $params = [$username, $email, $avatar, $userId];
                foreach ($userFields as $col => $val) {
                    $set .= ", `$col` = ?";
                    $params[] = $val;
                }
                $stmt = $pdo->prepare("UPDATE users SET $set WHERE id = ?");
                if ($stmt->execute($params)) {
                    // Update extra fields in role table if any
                    global $roleTable, $roleKey;
                    if ($roleTable && $extraFields) {
                        $set2 = '';
                        $params2 = [];
                        foreach ($extraFields as $col => $val) {
                            $set2 .= ($set2?', ':'') . "`$col` = ?";
                            $params2[] = $val;
                        }
                        if ($set2) {
                            $params2[] = $userId;
                            $pdo->prepare("UPDATE $roleTable SET $set2 WHERE $roleKey = ?")->execute($params2);
                        }
                    }
                    $_SESSION['success'] = "Profile updated successfully!";
                    session_regenerate_id(true);
                    header("Location: profile.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to update profile.";
                }
            }
        }
    }
}

// Function to handle avatar upload
function handleAvatarUpload($user, $userId) {
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

        // Only check getimagesize if file was uploaded
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
            $fileName = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
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

// Function to handle password changes
function handleChangePassword($pdo, $user, $userId) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $dbPassword = $stmt->fetchColumn();

    if (!password_verify($currentPassword, $dbPassword)) {
        $_SESSION['error'] = "Current password is incorrect.";
        error_log("Failed password change attempt for user ID: $userId");
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "New passwords do not match.";
    } elseif (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        $_SESSION['error'] = "Password must be at least 8 characters with at least one number and one uppercase letter.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $userId])) {
            $_SESSION['success'] = "Password changed successfully!";
            sendPasswordChangeNotification($user['email']);
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to change password.";
        }
    }
}

// Function to send password change notification
function sendPasswordChangeNotification($email) {
    error_log("Password changed notification sent to: $email");
}

?>

<style>
/* Profile Page Custom Styles */
.profile-main-container {
    max-width: 800px;
    margin: 40px auto;
    background: #fff;
    border-radius: 8px; /* Smaller card styling */
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05); /* Lighter shadow */
    padding: 24px; /* Reduced padding */
}
.profile-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 24px;
}
.profile-avatar {
    width: 100px; /* Smaller avatar size */
    height: 100px;
    margin-bottom: 12px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #007bff;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}
.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-info {
    text-align: center;
}
.profile-info h3 {
    margin: 0 0 6px 0;
    font-size: 1.25rem; /* Smaller font size */
    color: #222;
}
.profile-info .card-text {
    color: #555;
    margin-bottom: 4px;
}
.profile-info .badge {
    font-size: 0.9rem; /* Smaller badge size */
    margin-bottom: 6px;
}
.profile-info .text-muted {
    font-size: 0.85rem; /* Smaller text size */
}
.profile-forms-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px; /* Reduced gap */
    margin-top: 16px;
}
.profile-form-card {
    flex: 1 1 340px;
    background: #f8fafd;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    padding: 18px; /* Reduced padding */
    min-width: 320px;
}
.profile-form-card .card-header {
    background: #007bff;
    color: #fff;
    border-radius: 8px 8px 0 0;
    padding: 10px 16px; /* Reduced padding */
    margin: -18px -16px 16px -16px;
}
.profile-form-card .card-header.bg-secondary {
    background: #6c757d;
}
.erpnext-btn {
    background: #f5f7fa;
    color: #36414c;
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 6px 12px; /* Smaller button size */
    font-size: 0.875rem; /* Smaller font size */
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
}
.erpnext-btn.btn-primary {
    background: #007bfc;
    color: #fff;
    border-color: #007bfc;
}
.erpnext-btn.btn-primary:hover {
    background: #0056b3;
    color: #fff;
}
.erpnext-btn.btn-secondary {
    background: #6c757d;
    color: #fff;
    border-color: #6c757d;
}
.erpnext-btn.btn-secondary:hover {
    background: #5a6268;
}
.erpnext-input, .erpnext-textarea {
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 0.875rem; /* Smaller font size */
    background: #f5f7fa;
    color: #36414c;
}
.erpnext-input:focus, .erpnext-textarea:focus {
    outline: none;
    border-color: #007bfc;
    background: #fff;
}
@media (max-width: 600px) {
    .profile-main-container {
        padding: 10px 2vw;
    }
    .profile-header {
        padding: 0;
    }
    .profile-forms-row {
        flex-direction: column;
        gap: 12px; /* Reduced gap for smaller screens */
    }
}
</style>

<?php include_once 'includes/header.php'; ?>
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
                <div class="card-text"><?= htmlspecialchars($user['email'] ?? 'No email provided') ?></div>
                <span class="badge bg-primary"><?= htmlspecialchars($user['role_name'] ?? 'Unknown') ?></span>
                <div class="text-muted mt-2">Last Login: <?= !empty($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login']) ?? '') : 'Never' ?></div>
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
                                   class="erpnext-input"
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                   required
                                   pattern="[a-zA-Z0-9_]{3,30}"
                                   title="3-30 characters (letters, numbers, underscores)">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" 
                                   class="erpnext-input"
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Profile Picture:</label>
                            <input type="file" id="avatar" name="avatar" 
                                   class="erpnext-input"
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="form-text text-muted">Max 2MB (JPG, PNG, GIF only)</small>
                        </div>
                        <?php foreach ($userColumns as $col): ?>
                            <div class="mb-3">
                                <label for="<?= htmlspecialchars($col) ?>" class="form-label"><?= ucwords(str_replace('_',' ',$col)) ?>:</label>
                                <input type="text" id="<?= htmlspecialchars($col) ?>" name="user_fields[<?= htmlspecialchars($col) ?>]" class="erpnext-input" value="<?= htmlspecialchars($user[$col] ?? '') ?>">
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($extraColumns as $col): ?>
                            <div class="mb-3">
                                <label for="<?= htmlspecialchars($col) ?>" class="form-label"><?= ucwords(str_replace('_',' ',$col)) ?>:</label>
                                <input type="text" id="<?= htmlspecialchars($col) ?>" name="extra_fields[<?= htmlspecialchars($col) ?>]" class="erpnext-input" value="<?= htmlspecialchars($extraFields[$col] ?? '') ?>">
                            </div>
                        <?php endforeach; ?>
                        <div class="mb-3">
                            <label class="form-label">Role:</label>
                            <input type="text" class="erpnext-input" value="<?= htmlspecialchars($user['role_name']) ?>" readonly>
                        </div>
                        <button type="submit" class="erpnext-btn btn-primary w-100">Update Profile</button>
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
                                   class="erpnext-input" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password:</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="erpnext-input"
                                   required
                                   pattern="(?=.*\d)(?=.*[A-Z]).{8,}"
                                   title="Must contain at least one number, one uppercase letter, and be at least 8 characters">
                            <small class="form-text text-muted">Minimum 8 characters with at least one number and uppercase letter</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="erpnext-input" required>
                        </div>
                        <button type="submit" class="erpnext-btn btn-secondary w-100">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../includes/activity-tracker.js"></script>
</body>
</html>

