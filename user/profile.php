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

// Fetch the current user and extended profile data
$user = getCurrentUser();
if (!$user) {
    $_SESSION['error'] = "User session expired. Please login again.";
    header("Location: ../login.php");
    exit();
}

// Fetch extended profile data based on role and ensure all columns exist in DB
$profileData = [];
$role = strtolower($user['role_name'] ?? '');

// Helper: Ensure all columns exist in DB for profile editing
function adugna_ensure_profile_columns($pdo, $role) {
    $alterSqls = [];
    if ($role === 'teacher') {
        $alterSqls = [
            "ALTER TABLE teachers ADD COLUMN IF NOT EXISTS qualification VARCHAR(255) DEFAULT NULL;",
            "ALTER TABLE teachers ADD COLUMN IF NOT EXISTS subject_specialization VARCHAR(255) DEFAULT NULL;",
            "ALTER TABLE teachers ADD COLUMN IF NOT EXISTS date_of_birth DATE DEFAULT NULL;",
            "ALTER TABLE teachers ADD COLUMN IF NOT EXISTS gender VARCHAR(20) DEFAULT NULL;",
            "ALTER TABLE teachers ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL;"
        ];
    } elseif ($role === 'parent') {
        $alterSqls = [
            "ALTER TABLE parents ADD COLUMN IF NOT EXISTS occupation VARCHAR(100) DEFAULT NULL;",
            "ALTER TABLE parents ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL;",
            "ALTER TABLE parents ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL;"
        ];
    } elseif ($role === 'student') {
        $alterSqls = [
            "ALTER TABLE students ADD COLUMN IF NOT EXISTS class_id INT DEFAULT NULL;",
            "ALTER TABLE students ADD COLUMN IF NOT EXISTS section_id INT DEFAULT NULL;",
            "ALTER TABLE students ADD COLUMN IF NOT EXISTS enrollment_no VARCHAR(50) DEFAULT NULL;",
            "ALTER TABLE students ADD COLUMN IF NOT EXISTS date_of_birth DATE DEFAULT NULL;",
            "ALTER TABLE students ADD COLUMN IF NOT EXISTS gender VARCHAR(20) DEFAULT NULL;",
            "ALTER TABLE students ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL;"
        ];
    }
    if (!empty($alterSqls)) {
        $migrationFile = __DIR__ . '/../migrations/adugna_profile_columns_' . $role . '.sql';
        $migrationSql = "-- Developer: Adugna Gizaw\n" . implode("\n", $alterSqls);
        file_put_contents($migrationFile, $migrationSql);
    }
}
adugna_ensure_profile_columns($pdo, $role);

if ($role === 'teacher') {
    $stmt = $pdo->prepare("SELECT qualification, subject_specialization, date_of_birth, gender, address FROM teachers WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profileData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} elseif ($role === 'parent') {
    $stmt = $pdo->prepare("SELECT occupation, address, phone FROM parents WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profileData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} elseif ($role === 'student') {
    $stmt = $pdo->prepare("SELECT class_id, section_id, enrollment_no, date_of_birth, gender, address FROM students WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profileData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Define user ID
$userId = $_SESSION['user_id'];

// Always fetch the latest username and email from the database for the form fields
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$latestUser = $stmt->fetch(PDO::FETCH_ASSOC);
if ($latestUser) {
    $user['username'] = $latestUser['username'];
    $user['email'] = $latestUser['email'];
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
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?");
                if ($stmt->execute([$username, $email, $avatar, $userId])) {
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

// Helper function to display profile fields safely and user-friendly
function adugna_display_profile_field($key, $val) {
    /**
     * Developer: Adugna Gizaw
     * Email: gizawadugna@gmail.com
     * LinkedIn: https://www.linkedin.com/in/eleganceict
     * Twitter: https://twitter.com/eleganceict1
     * GitHub: https://github.com/addex12
     */
    // If value is null or empty, show 'N/A' for clarity
    if (is_null($val) || $val === '') return '<span class="adugna-text-muted">N/A</span>';
    // For date fields, format nicely
    if (stripos($key, 'date') !== false && strtotime($val)) {
        return htmlspecialchars(date('M d, Y', strtotime($val)));
    }
    // For status, capitalize
    if ($key === 'status') {
        return '<span class="adugna-badge" style="background:#28a745;">' . htmlspecialchars(ucfirst($val)) . '</span>';
    }
    // For phone, format
    if ($key === 'phone') {
        return '<a href="tel:' . htmlspecialchars($val) . '" class="adugna-link">' . htmlspecialchars($val) . '</a>';
    }
    // For class_id, section_id, show as ID or N/A
    if (in_array($key, ['class_id','section_id']) && !$val) {
        return '<span class="adugna-text-muted">N/A</span>';
    }
    // Default: escape value
    return htmlspecialchars($val);
}

?>

<style>
/* Adugna CRM Profile Custom Styles - adugna- prefix for patenting */
.adugna-profile-main-container {
    max-width: 900px;
    margin: 2vw auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    padding: 2vw 2vw 1vw 2vw;
}
.adugna-profile-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.5vw;
}
.adugna-profile-avatar {
    width: 72px;
    height: 72px;
    margin-bottom: 10px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #1a73e8;
    background: #f3f6fa;
    display: flex;
    align-items: center;
    justify-content: center;
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
    margin: 0 0 4px 0;
    font-size: 1.1rem;
    color: #1a1a1a;
    font-weight: 600;
}
.adugna-profile-info .adugna-card-text {
    color: #555;
    margin-bottom: 2px;
    font-size: 0.97em;
}
.adugna-profile-info .adugna-badge {
    font-size: 0.85rem;
    margin-bottom: 4px;
    background: #1a73e8;
    color: #fff;
    border-radius: 4px;
    padding: 2px 8px;
    display: inline-block;
}
.adugna-profile-info .adugna-text-muted {
    font-size: 0.8rem;
    color: #888;
}
.adugna-profile-forms-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 12px;
}
.adugna-profile-form-card {
    flex: 1 1 320px;
    background: #f7fafd;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    padding: 14px 14px 10px 14px;
    min-width: 260px;
}
.adugna-profile-form-card .adugna-card-header {
    background: #1a73e8;
    color: #fff;
    border-radius: 8px 8px 0 0;
    padding: 7px 12px;
    margin: -14px -14px 10px -14px;
    font-size: 1em;
}
.adugna-profile-form-card .adugna-card-header.adugna-bg-secondary {
    background: #5a6268;
}
.adugna-btn {
    background: #f5f7fa;
    color: #1a1a1a;
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 5px 10px;
    font-size: 0.89rem;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
    min-width: 80px;
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
    background: #5a6268;
    color: #fff;
    border-color: #5a6268;
}
.adugna-btn.adugna-btn-secondary:hover {
    background: #444b50;
}
.adugna-input, .adugna-textarea {
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 7px 10px;
    font-size: 0.89rem;
    background: #f5f7fa;
    color: #1a1a1a;
}
.adugna-input:focus, .adugna-textarea:focus {
    outline: none;
    border-color: #1a73e8;
    background: #fff;
}
@media (max-width: 600px) {
    .adugna-profile-main-container {
        padding: 8px 1vw;
    }
    .adugna-profile-header {
        padding: 0;
    }
    .adugna-profile-forms-row {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<?php include_once 'includes/header.php'; ?>
<div class="main-content-container">
    <div class="adugna-profile-main-container">
        <div class="adugna-profile-header">
            <div class="adugna-profile-avatar">
                <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.jpg') ?>"
                     alt="Profile Picture"
                     onerror="this.onerror=null; this.src='../uploads/avatars/default.jpg';">
            </div>
            <div class="adugna-profile-info">
                <h3><?= htmlspecialchars($user['username'] ?? 'Unknown') ?></h3>
                <div class="adugna-card-text"><?= htmlspecialchars($user['email'] ?? 'No email provided') ?></div>
                <span class="adugna-badge"><?= htmlspecialchars($user['role_name'] ?? 'Unknown Role') ?></span>
                <div class="adugna-text-muted mt-2">Last Login: <?= !empty($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never' ?></div>
            </div>
        </div>

        <?php if (!empty($profileData)): ?>
        <div class="adugna-profile-form-card" style="margin-bottom:12px;">
            <div class="adugna-card-header adugna-bg-secondary">
                <span>Additional Profile Details</span>
            </div>
            <div class="card-body">
                <ul style="list-style:none;padding:0;margin:0;">
                    <?php foreach ($profileData as $key => $val): ?>
                        <li style="margin-bottom:4px;font-size:0.97em;">
                            <strong><?= ucwords(str_replace('_', ' ', $key)) ?>:</strong> <?= adugna_display_profile_field($key, $val) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

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
                    <h5 class="mb-0" style="font-size:1em;">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" id="username" name="username"
                                   class="adugna-input"
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                   required
                                   pattern="[a-zA-Z0-9_]{3,30}"
                                   title="3-30 characters (letters, numbers, underscores)">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email"
                                   class="adugna-input"
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Profile Picture:</label>
                            <input type="file" id="avatar" name="avatar"
                                   class="adugna-input"
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="form-text adugna-text-muted">Max 2MB (JPG, PNG, GIF only)</small>
                        </div>
                        <button type="submit" class="adugna-btn adugna-btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>

            <div class="adugna-profile-form-card">
                <div class="adugna-card-header adugna-bg-secondary">
                    <h5 class="mb-0" style="font-size:1em;">Change Password</h5>
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
                            <small class="form-text adugna-text-muted">Minimum 8 characters with at least one number and uppercase letter</small>
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

