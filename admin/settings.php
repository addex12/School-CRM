<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/setting.php'; // Contains getSystemSetting()

// Prevent duplicate session starts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

// Enhanced file upload handler
function handleFileUpload($fileInput, $settingKey, $allowedTypes, $maxSize = 1024000) {
    global $pdo;
    
    if (!isset($_FILES[$fileInput])) {
        return;
    }
    
    $file = $_FILES[$fileInput];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return;
    }
    
    // Validate file
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        throw new Exception("Invalid file type for $settingKey");
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception("File size too large for $settingKey");
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $fileExt;
    $uploadPath = '../assets/uploads/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'media')");
        $stmt->execute([$settingKey, $filename]);
        return $filename;
    }
    
    return null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Process text settings
        if (isset($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $value) {
                $value = htmlspecialchars(trim($value));
                
                // Validate specific settings
                switch ($key) {
                    case 'site_email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Invalid email format");
                        }
                        break;
                        
                    case 'items_per_page':
                        if (!is_numeric($value) || $value < 1 || $value > 100) {
                            throw new Exception("Items per page must be between 1-100");
                        }
                        break;
                }
                
                $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        }
        
        // Handle file uploads
        handleFileUpload('site_logo', 'site_logo', ['png', 'jpg', 'jpeg', 'gif']);
        handleFileUpload('favicon', 'favicon', ['ico', 'png']);
        handleFileUpload('og_image', 'og_image', ['png', 'jpg', 'jpeg']);
        
        $_SESSION['success'] = "Settings updated successfully!";
        header("Location: settings.php");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Enhanced default settings
$defaultSettings = [
    'general' => [
        'site_name' => 'School Survey System',
        'site_email' => 'admin@school.edu',
        'timezone' => 'UTC',
        'items_per_page' => 10,
        'maintenance_mode' => 0
    ],
    'appearance' => [
        'theme_color' => '#3498db',
        'font_family' => 'Arial',
        'dark_mode' => 0
    ],
    'media' => [
        'site_logo' => '',
        'favicon' => '',
        'og_image' => ''
    ],
    'security' => [
        'login_attempts' => 5,
        'password_expiry' => 90,
        '2fa_enabled' => 0
    ],
    'notifications' => [
        'email_notifications' => 1,
        'slack_webhook' => '',
        'sms_gateway' => ''
    ],
    'email' => [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_secure' => 'tls',
        'smtp_username' => '',
        'smtp_password' => ''
    ]
];

// Merge with database values
foreach ($defaultSettings as $group => $groupSettings) {
    foreach ($groupSettings as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/admin.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?> <!-- Corrected the path -->
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?> <!-- Ensure this path is also correct -->
        
        <main class="settings-main">
            <h1>System Settings</h1>
            
            <?php if ($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="settings-tabs">
                    <button type="button" class="tab-btn active" data-tab="general">General</button>
                    <button type="button" class="tab-btn" data-tab="appearance">Appearance</button>
                    <button type="button" class="tab-btn" data-tab="security">Security</button>
                    <button type="button" class="tab-btn" data-tab="notifications">Notifications</button>
                    <button type="button" class="tab-btn" data-tab="media">Media</button>
                    <button type="button" class="tab-btn" data-tab="email">Email</button>
                </div>

                <!-- General Settings Tab -->
                <div class="tab-content active" id="general">
                    <!-- Enhanced General Settings Form Fields -->
                </div>

                <!-- Appearance Settings Tab -->
                <div class="tab-content" id="appearance">
                    <!-- Enhanced Appearance Settings Form Fields -->
                </div>

                <!-- Security Settings Tab -->
                <div class="tab-content" id="security">
                    <!-- Enhanced Security Settings Form Fields -->
                </div>

                <!-- Notifications Tab -->
                <div class="tab-content" id="notifications">
                    <!-- Notifications Settings Form Fields -->
                </div>

                <!-- Media Tab -->
                <div class="tab-content" id="media">
                    <!-- Media Upload Form Fields -->
                </div>

                <!-- Email Settings Tab -->
                <div class="tab-content" id="email">
                    <!-- Enhanced Email Settings Form Fields -->
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">Save All Changes</button>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Enhanced tab functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Tab switching logic
            });
        });

        // Live preview for theme color
        document.getElementById('theme_color').addEventListener('input', function() {
            document.documentElement.style.setProperty('--primary-color', this.value);
        });
    </script>
</body>
</html>