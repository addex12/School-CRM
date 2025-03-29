<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/setting.php'; // Contains getSystemSetting()

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$success = '';

// Enhanced file upload handler with validation
function handleFileUpload($fileInput, $settingKey, $allowedTypes, $maxSize = 2 * 1024 * 1024) {
    global $pdo;
    
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) return null;

    $file = $_FILES[$fileInput];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file
    if (!in_array($fileExt, $allowedTypes)) {
        throw new Exception("Invalid file type for $settingKey. Allowed types: " . implode(', ', $allowedTypes));
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception("File size too large for $settingKey. Max size: " . ($maxSize / 1024 / 1024) . "MB");
    }

    // Generate unique filename and move to uploads
    $filename = uniqid() . '.' . $fileExt;
    $uploadPath = '../assets/uploads/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");
        $group = ($settingKey === 'og_image') ? 'media' : 'appearance';
        $stmt->execute([$settingKey, $filename, $group]);
        return $filename;
    }
    
    throw new Exception("Failed to upload $settingKey file");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }

        // Process text settings
        if (isset($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $value) {
                $value = htmlspecialchars(trim($value));
                
                // Special validation rules
                switch ($key) {
                    case 'site_email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Invalid email format");
                        }
                        break;
                        
                    case 'items_per_page':
                    case 'login_attempts':
                    case 'password_expiry':
                        if (!is_numeric($value) || $value < 1) {
                            throw new Exception("Invalid value for $key");
                        }
                        break;
                        
                    case 'theme_color':
                        if (!preg_match('/^#[a-f0-9]{6}$/i', $value)) {
                            throw new Exception("Invalid color format");
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

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default settings structure
$settingGroups = [
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
        'dark_mode' => 0,
        'site_logo' => '',
        'favicon' => ''
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
    'media' => [
        'og_image' => ''
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
foreach ($settingGroups as $group => $keys) {
    foreach ($keys as $key => $default) {
        if (isset($settings[$key])) {
            $settingGroups[$group][$key] = $settings[$key];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
</head>
<body>
   
    <div class="admin-container">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="settings-main">
            <h1><i class="fas fa-cog"></i> System Settings</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="settings-tabs">
                    <?php foreach ($settingGroups as $group => $settings): ?>
                        <button type="button" class="tab-btn <?= $group === 'general' ? 'active' : '' ?>" 
                                data-tab="<?= $group ?>">
                            <?= ucfirst($group) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($settingGroups as $group => $groupSettings): ?>
                    <div class="tab-content <?= $group === 'general' ? 'active' : '' ?>" id="<?= $group ?>-tab">
                        <div class="settings-group">
                            <h2><?= ucfirst($group) ?> Settings</h2>
                            
                            <?php foreach ($groupSettings as $key => $value): ?>
                                <div class="form-group">
                                    <label><?= str_replace('_', ' ', ucfirst($key)) ?></label>
                                    
                                    <?php if ($key === 'timezone'): ?>
                                        <select name="settings[<?= $key ?>]" class="form-control">
                                            <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                                                <option value="<?= $tz ?>" <?= $value === $tz ? 'selected' : '' ?>>
                                                    <?= $tz ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    
                                    <?php elseif (strpos($key, 'password') !== false): ?>
                                        <input type="password" name="settings[<?= $key ?>]" 
                                               value="<?= htmlspecialchars($value) ?>" class="form-control">
                                    
                                    <?php elseif ($key === 'theme_color'): ?>
                                        <div class="color-picker">
                                            <input type="color" name="settings[<?= $key ?>]" 
                                                   value="<?= htmlspecialchars($value) ?>">
                                            <span class="color-preview" style="background: <?= htmlspecialchars($value) ?>"></span>
                                        </div>
                                    
                                    <?php elseif (in_array($key, ['site_logo', 'favicon', 'og_image'])): ?>
                                        <div class="file-upload">
                                            <input type="file" name="<?= $key ?>" accept="<?= 
                                                $key === 'favicon' ? 'image/x-icon' : 'image/*' ?>">
                                            <?php if ($value): ?>
                                                <div class="current-file">
                                                    Current: <a href="../assets/uploads/<?= $value ?>" target="_blank"><?= $value ?></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    
                                    <?php elseif (in_array($key, ['2fa_enabled', 'email_notifications', 'dark_mode', 'maintenance_mode'])): ?>
                                        <label class="switch">
                                            <input type="checkbox" name="settings[<?= $key ?>]" 
                                                   <?= $value ? 'checked' : '' ?> value="1">
                                            <span class="slider"></span>
                                        </label>
                                    
                                    <?php else: ?>
                                        <input type="text" name="settings[<?= $key ?>]" 
                                               value="<?= htmlspecialchars($value) ?>" class="form-control">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn, .tab-content').forEach(el => {
                    el.classList.remove('active');
                });
                btn.classList.add('active');
                document.getElementById(`${btn.dataset.tab}-tab`).classList.add('active');
            });
        });

        // Initialize Choices.js for better select inputs
        new Choices('[name="settings[timezone]"]', {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false,
        });

        // Theme color preview
        document.querySelector('input[name="settings[theme_color]"]').addEventListener('input', function() {
            document.querySelector('.color-preview').style.backgroundColor = this.value;
        });
    </script>
</body>
</html>