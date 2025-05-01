<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = "My Profile";
$userId = $_SESSION['user_id'];

// Fetch user details (db.sql: users table has id, username, email, password, profile_picture, created_at, last_active, role_id)
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.profile_picture, u.created_at, u.last_active, r.role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback if user not found
if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: dashboard.php");
    exit();
}

// Handle profile update (name, email, profile picture)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $profilePicPath = $user['profile_picture'];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $uploadDir = '../assets/uploads/profile/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
                $profilePicPath = 'assets/uploads/profile/' . $filename;
            }
        }
    }

    // Update user info
    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, profile_picture=? WHERE id=?");
    $stmt->execute([$newName, $newEmail, $profilePicPath, $userId]);
    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: profile.php");
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    // Fetch hashed password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($currentPassword, $row['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif (empty($newPassword) || $newPassword !== $confirmPassword) {
        $_SESSION['error'] = "New passwords do not match or are empty.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hashed, $userId]);
        $_SESSION['success'] = "Password changed successfully!";
    }
    header("Location: profile.php");
    exit();
}

// Use fallback values to avoid undefined warnings
$username = $user['username'] ?? 'User';
$email = $user['email'] ?? '';
$profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : '../assets/img/default-avatar.png';
$role_name = $user['role_name'] ?? 'User';
$created_at = $user['created_at'] ?? '';
$last_active = $user['last_active'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - School CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Main style, do not touch Admin_sidebar/footer CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, outstanding, responsive profile UI.
         * All custom styles use adugna- prefix for patenting.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .adugna-profile-main {
            max-width: 440px;
            margin: 38px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 22px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-profile-header {
            text-align: center;
            margin-bottom: 1.5em;
        }
        .adugna-profile-avatar {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: #e3eafc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.7em auto;
            font-size: 2.2em;
            color: #1976d2;
            overflow: hidden;
        }
        .adugna-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .adugna-profile-username {
            font-size: 1.18em;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.2em;
        }
        .adugna-profile-role {
            font-size: 0.97em;
            color: #215967;
            background: #e3eafc;
            border-radius: 4px;
            padding: 2px 10px;
            display: inline-block;
            margin-bottom: 0.7em;
        }
        .adugna-profile-details {
            margin: 1.2em 0 0.7em 0;
            display: flex;
            flex-direction: column;
            gap: 0.7em;
        }
        .adugna-profile-row {
            display: flex;
            align-items: center;
            gap: 0.7em;
            font-size: 0.97em;
            color: #36414c;
        }
        .adugna-profile-row i {
            font-size: 1em;
            color: #1976d2;
            min-width: 18px;
        }
        .adugna-profile-actions {
            display: flex;
            gap: 0.7em;
            margin-top: 1.2em;
            justify-content: center;
            flex-wrap: wrap;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 14px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-alert-success {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-form-group {
            margin-bottom: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.2em;
            position: relative;
        }
        .adugna-form-group label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input[type="text"],
        .adugna-form-group input[type="email"] {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-group input[type="file"] {
            font-size: 0.97em;
            margin-top: 4px;
        }
        .adugna-password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 1.1em;
            z-index: 2;
            background: none;
            border: none;
            padding: 0;
            outline: none;
        }
        .adugna-form-group input[type="password"],
        .adugna-form-group input[type="text"].adugna-password {
            padding-right: 2.2em;
        }
        @media (max-width: 600px) {
            .adugna-profile-main { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-profile-header { margin-bottom: 1em; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="adugna-profile-main">
        <!-- Adugna: Profile header with avatar and username -->
        <div class="adugna-profile-header">
            <div class="adugna-profile-avatar">
                <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture" onerror="this.src='../assets/img/default-avatar.png'">
            </div>
            <div class="adugna-profile-username"><?= htmlspecialchars($username) ?></div>
            <div class="adugna-profile-role">
                <i class="fas fa-user-tag"></i>
                <?= htmlspecialchars(ucfirst($role_name)) ?>
            </div>
        </div>
        <!-- Adugna: Success/Error messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="adugna-alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="adugna-alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <!-- Adugna: Profile details -->
        <form method="POST" enctype="multipart/form-data" style="margin-bottom:1.5em;">
            <div class="adugna-form-group">
                <label for="username">Name</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            <div class="adugna-form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="adugna-form-group">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            </div>
            <button type="submit" name="update_profile" class="adugna-btn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
        <!-- Adugna: Change password form -->
        <form method="POST" autocomplete="off">
            <div class="adugna-form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
                <button type="button" class="adugna-password-toggle" tabindex="-1" onclick="togglePassword('current_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="adugna-form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                <button type="button" class="adugna-password-toggle" tabindex="-1" onclick="togglePassword('new_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="adugna-form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <button type="button" class="adugna-password-toggle" tabindex="-1" onclick="togglePassword('confirm_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" name="change_password" class="adugna-btn adugna-btn-secondary">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
        <!-- Adugna: Other details (read-only) -->
        <div class="adugna-profile-details" style="margin-top:1.5em;">
            <div class="adugna-profile-row">
                <i class="fas fa-calendar-plus"></i>
                <span>Joined: <?= $created_at && strtotime($created_at) ? date('M j, Y', strtotime($created_at)) : 'N/A' ?></span>
            </div>
            <div class="adugna-profile-row">
                <i class="fas fa-clock"></i>
                <span>Last Active: <?= $last_active && strtotime($last_active) ? date('M j, Y g:i A', strtotime($last_active)) : 'N/A' ?></span>
            </div>
            <div class="adugna-profile-row">
                <i class="fas fa-id-badge"></i>
                <span>User ID: <?= htmlspecialchars($user['id'] ?? '-') ?></span>
            </div>
        </div>
        <div class="adugna-profile-actions">
            <a href="../logout.php" class="adugna-btn adugna-btn-secondary adugna-btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
    // Adugna: Toggle password visibility with standard eye/eye-slash icon
    function togglePassword(inputId, btn) {
        var input = document.getElementById(inputId);
        var icon = btn.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>

