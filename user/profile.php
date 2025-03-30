<?php
ob_start();
session_start();

// Error reporting - consider logging to file in production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Include required files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch the current user
$user = getCurrentUser();
if (!$user) {
    $_SESSION['error'] = "User session expired. Please login again.";
    header("Location: ../login.php");
    exit();
}

// Define user ID
$userId = $_SESSION['user_id'];

// Handle form submissions (keep your existing form handling code)
// ... [Your existing form handling code remains unchanged] ...

include __DIR__ . '/../includes/header.php';
?>

<style>
/* Modern Profile Page Styles */
.profile-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.profile-header {
    text-align: center;
    margin-bottom: 2rem;
    color: #2c3e50;
}

.profile-section {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 2rem;
}

.profile-sidebar {
    flex: 1;
    min-width: 300px;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.profile-content {
    flex: 2;
    min-width: 300px;
}

.profile-avatar {
    margin: 0 auto 1.5rem;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid #fff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.profile-info p {
    margin: 0.5rem 0;
    color: #7f8c8d;
}

.role-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    background: #3498db;
    color: white;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

.profile-card {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.profile-card h2 {
    margin-top: 0;
    color: #2c3e50;
    border-bottom: 1px solid #eee;
    padding-bottom: 0.8rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.2rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #34495e;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="file"] {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.btn {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s;
}

.btn:hover {
    background: #2980b9;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #7f8c8d;
}

@media (max-width: 768px) {
    .profile-section {
        flex-direction: column;
    }
    
    .profile-sidebar, .profile-content {
        width: 100%;
    }
}
</style>

<div class="profile-container">
    <div class="profile-header">
        <h1>My Profile</h1>
        <p>Manage your account information and security settings</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="profile-section">
        <!-- Profile Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.jpg') ?>" 
                     alt="Profile Picture" 
                     class="avatar-img"
                     onerror="this.src='../uploads/avatars/default.jpg'">
            </div>
            
            <div class="profile-info">
                <h3><?= htmlspecialchars($user['username'] ?? 'Unknown') ?></h3>
                <p><?= htmlspecialchars($user['email'] ?? 'No email provided') ?></p>
                <p class="role-badge"><?= htmlspecialchars($user['role_name'] ?? 'Unknown Role') ?></p>
                
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eee;">
                    <p><strong>Last Login:</strong> <?= !empty($user['last_login']) ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never' ?></p>
                    <p><strong>Member Since:</strong> <?= date('M Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Update Profile Form -->
            <div class="profile-card">
                <h2>Profile Information</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                               required
                               pattern="[a-zA-Z0-9_]{3,30}"
                               title="3-30 characters (letters, numbers, underscores)">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="avatar">Profile Picture</label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif">
                        <small class="form-text">JPG, PNG or GIF (Max 2MB)</small>
                    </div>
                    
                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="profile-card">
                <h2>Change Password</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               required
                               pattern="(?=.*\d)(?=.*[A-Z]).{8,}"
                               title="Must contain at least one number, one uppercase letter, and be at least 8 characters">
                        <small class="form-text">Minimum 8 characters with at least one number and uppercase letter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; 
ob_end_flush();
?>