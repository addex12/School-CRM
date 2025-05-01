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

// Fetch the current user and all columns, including role name
$user = getCurrentUser();
if (!$user) {
    $_SESSION['error'] = "User session expired. Please login again.";
    header("Location: ../login.php");
    exit();
}
// Always fetch username, email, and role from users table (joined with roles)
$username = $user['username'] ?? '';
$email = $user['email'] ?? '';
$roleName = $user['role_name'] ?? '';

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

// Get all user table columns except id, password, role_id, avatar, username, email, created_at, last_active, last_login, online, active, tracking_token, remember_token, role_name
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
        // After update, re-fetch user data to get the latest avatar and other info
        $user = getCurrentUser();
        // Also re-fetch extra fields if needed
        if ($roleTable) {
            $stmt = $pdo->prepare("SELECT * FROM $roleTable WHERE $roleKey = ? LIMIT 1");
            $stmt->execute([$user['id']]);
            $extraFields = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
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

    // Remove class_id and section_id from extraFields for students (read-only)
    global $roleTable;
    if ($roleTable === 'students') {
        unset($extraFields['class_id'], $extraFields['section_id']);
    }

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
            $avatar = handleAvatarUpload($user, $userId); // This will return the new filename if uploaded, or the old one
            if ($avatar !== false) {
                // Always use the new avatar filename or the old one
                $set = 'username = ?, email = ?, avatar = ?';
                $params = [$username, $email, $avatar];
                foreach ($userFields as $col => $val) {
                    $set .= ", `$col` = ?";
                    $params[] = $val;
                }
                $params[] = $userId; // userId must be last for WHERE id = ?
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
/* adugna-Profile Page Custom Styles - Compact, Responsive, and Branded */
.adugna-profile-main-container {
    max-width: 820px;
    margin: 32px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    padding: 18px 10px 24px 10px;
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
}
.adugna-profile-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 18px;
}
.adugna-profile-avatar {
    width: 72px;
    height: 72px;
    margin-bottom: 8px;
    border-radius: 50%;
    overflow: hidden;
    border: 2.5px solid #1a73e8;
    background: #f3f6fa;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.adugna-profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.adugna-profile-info {
    text-align: center;
}
.adugna-profile-info h3 {
    margin: 0 0 2px 0;
    font-size: 1.08rem;
    color: #1a1a1a;
    font-weight: 600;
}
.adugna-profile-info .adugna-card-text {
    color: #4a4a4a;
    margin-bottom: 2px;
    font-size: 0.97rem;
}
.adugna-profile-info .adugna-badge {
    font-size: 0.78rem;
    background: #e3f0fc;
    color: #1a73e8;
    border-radius: 4px;
    padding: 2px 8px;
    margin-bottom: 4px;
    display: inline-block;
    font-weight: 500;
}
.adugna-profile-info .adugna-text-muted {
    font-size: 0.78rem;
    color: #888;
}
.adugna-profile-forms-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}
.adugna-profile-form-card {
    flex: 1 1 320px;
    background: #f7fafd;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    padding: 12px 10px 16px 10px;
    min-width: 260px;
    border: 1.5px solid #e3e8ef;
}
.adugna-profile-form-card .adugna-card-header {
    background: #1a73e8;
    color: #fff;
    border-radius: 8px 8px 0 0;
    padding: 7px 12px;
    margin: -12px -10px 10px -10px;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 0.01em;
}
.adugna-profile-form-card .adugna-card-header.adugna-bg-secondary {
    background: #5a5a5a;
}
.adugna-btn {
    background: #f5f7fa;
    color: #1a1a1a;
    border: 1.2px solid #d1d8dd;
    border-radius: 4px;
    padding: 4px 10px;
    font-size: 0.89rem;
    font-weight: 500;
    transition: background 0.18s, color 0.18s;
    cursor: pointer;
    min-width: 80px;
    min-height: 28px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.adugna-btn.adugna-btn-primary {
    background: #1a73e8;
    color: #fff;
    border-color: #1a73e8;
}
.adugna-btn.adugna-btn-primary:hover {
    background: #155ab6;
    color: #fff;
}
.adugna-btn.adugna-btn-secondary {
    background: #5a5a5a;
    color: #fff;
    border-color: #5a5a5a;
}
.adugna-btn.adugna-btn-secondary:hover {
    background: #333;
}
.adugna-input, .adugna-textarea {
    border: 1.2px solid #d1d8dd;
    border-radius: 4px;
    padding: 6px 10px;
    font-size: 0.89rem;
    background: #f5f7fa;
    color: #1a1a1a;
    width: 100%;
    box-sizing: border-box;
    margin-bottom: 2px;
}
.adugna-input:focus, .adugna-textarea:focus {
    outline: none;
    border-color: #1a73e8;
    background: #fff;
}
.adugna-icon {
    font-size: 1.1em;
    vertical-align: middle;
    margin-right: 3px;
    color: #1a73e8;
}
@media (max-width: 700px) {
    .adugna-profile-main-container {
        padding: 6px 1vw;
    }
    .adugna-profile-header {
        padding: 0;
    }
    .adugna-profile-forms-row {
        flex-direction: column;
        gap: 8px;
    }
    .adugna-profile-form-card {
        min-width: 0;
        padding: 8px 4px 12px 4px;
    }
}
@media (max-width: 400px) {
    .adugna-profile-main-container {
        padding: 2px 0.5vw;
    }
    .adugna-profile-form-card {
        padding: 4px 2px 8px 2px;
    }
    .adugna-profile-avatar {
        width: 48px;
        height: 48px;
    }
    .adugna-profile-info h3 {
        font-size: 0.98rem;
    }
}
</style>

<?php include_once 'includes/header.php'; ?>
<div class="main-content-container">
    <div class="adugna-profile-main-container">
        <div class="adugna-profile-header">
            <div class="adugna-profile-avatar">
            <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.jpg') ?>?v=<?= time() ?>"
     alt="Profile Picture"
     onerror="this.onerror=null; this.src='../uploads/avatars/default.jpg';"></div>
            <div class="adugna-profile-info">
                <h3><?= htmlspecialchars($username) ?></h3>
                <div class="adugna-card-text"><?= htmlspecialchars($email) ?></div>
                <span class="adugna-badge"><?= htmlspecialchars($roleName) ?></span>
                <div class="adugna-text-muted mt-2">Last Login: <?= !empty($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login']) ?? '') : 'Never' ?></div>            </div>
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
            <div class="adugna-profile-form-card">
                <div class="adugna-card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" id="username" name="username" 
                                   class="adugna-input"
                                   value="<?= htmlspecialchars($username) ?>" 
                                   required
                                   pattern="[a-zA-Z0-9_]{3,30}"
                                   title="3-30 characters (letters, numbers, underscores)">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" 
                                   class="adugna-input"
                                   value="<?= htmlspecialchars($email) ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Profile Picture:</label>
                            <input type="file" id="avatar" name="avatar" 
                                   class="adugna-input"
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="form-text text-muted">Max 2MB (JPG, PNG, GIF only)</small>
                        </div>
                        <?php foreach ($userColumns as $col): ?>
                            <div class="mb-3">
                                <label for="<?= htmlspecialchars($col) ?>" class="form-label"><?= ucwords(str_replace('_',' ',$col)) ?>:</label>
                                <input type="text" id="<?= htmlspecialchars($col) ?>" name="user_fields[<?= htmlspecialchars($col) ?>]" class="adugna-input" value="<?= htmlspecialchars($user[$col] ?? '') ?>">
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($extraColumns as $col): ?>
                            <div class="mb-3">
                                <label for="<?= htmlspecialchars($col) ?>" class="form-label"><?= ucwords(str_replace('_',' ',$col)) ?>:</label>
                                <?php if ($roleTable === 'students' && in_array($col, ['class_id', 'section_id'])): ?>
                                    <input type="text" id="<?= htmlspecialchars($col) ?>" name="extra_fields[<?= htmlspecialchars($col) ?>]" class="adugna-input" value="<?= htmlspecialchars($extraFields[$col] ?? '') ?>" readonly>
                                <?php else: ?>
                                    <input type="text" id="<?= htmlspecialchars($col) ?>" name="extra_fields[<?= htmlspecialchars($col) ?>]" class="adugna-input" value="<?= htmlspecialchars($extraFields[$col] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="mb-3">
                            <label class="form-label">Role:</label>
                            <input type="text" class="adugna-input" value="<?= htmlspecialchars($roleName) ?>" readonly>
                        </div>
                        <button type="submit" class="adugna-btn adugna-btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="adugna-profile-form-card">
                <div class="adugna-card-header adugna-bg-secondary">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password:</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="adugna-input" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password:</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="adugna-input"
                                   required
                                   pattern="(?=.*\d)(?=.*[A-Z]).{8,}"
                                   title="Must contain at least one number, one uppercase letter, and be at least 8 characters">
                            <small class="form-text text-muted">Minimum 8 characters with at least one number and uppercase letter</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="adugna-input" required>
                        </div>
                        <button type="submit" class="adugna-btn adugna-btn-secondary w-100">Change Password</button>
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

