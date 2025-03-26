<?php
require_once '../includes/auth.php';
requireAdmin();
include 'includes/header.php';
require_once '../includes/config.php'; // Include config to initialize $pdo
require_once '../includes/auth.php';
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        // Check if setting exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            // Update existing setting
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            // Insert new setting
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'general')");
            $stmt->execute([$key, $value]);
        }
    }
    
    // Handle file uploads (logo, favicon)
    if (!empty($_FILES['site_logo']['name'])) {
        $upload_dir = '../assets/images/';
        $filename = 'logo.' . pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $filepath)) {
            // Update logo setting
            $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value, setting_group) VALUES ('site_logo', ?, 'appearance')");
            $stmt->execute([$filename]);
        }
    }
    
    if (!empty($_FILES['favicon']['name'])) {
        $upload_dir = '../assets/images/';
        $filename = 'favicon.' . pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $filepath)) {
            // Update favicon setting
            $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value, setting_group) VALUES ('favicon', ?, 'appearance')");
            $stmt->execute([$filename]);
        }
    }
    
    $_SESSION['success'] = "Settings updated successfully!";
    header("Location: settings.php");
    exit();
}

// Get all settings grouped by category
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_group']][] = $row;
}

// Default settings if not in database
$default_settings = [
    'general' => [
        ['setting_key' => 'site_name', 'setting_value' => 'School Survey System'],
        ['setting_key' => 'site_email', 'setting_value' => 'admin@school.edu'],
        ['setting_key' => 'timezone', 'setting_value' => 'UTC'],
        ['setting_key' => 'items_per_page', 'setting_value' => '10']
    ],
    'appearance' => [
        ['setting_key' => 'site_logo', 'setting_value' => ''],
        ['setting_key' => 'favicon', 'setting_value' => ''],
        ['setting_key' => 'theme_color', 'setting_value' => '#3498db']
    ],
    'email' => [
        ['setting_key' => 'smtp_provider', 'setting_value' => ''],
        ['setting_key' => 'smtp_host', 'setting_value' => ''],
        ['setting_key' => 'smtp_port', 'setting_value' => '587'],
        ['setting_key' => 'smtp_username', 'setting_value' => ''],
        ['setting_key' => 'smtp_password', 'setting_value' => ''],
        ['setting_key' => 'smtp_secure', 'setting_value' => 'tls']
    ]
];

// Merge default settings with database settings
foreach ($default_settings as $group => $group_settings) {
    if (!isset($settings[$group])) {
        $settings[$group] = [];
    }
    
    foreach ($group_settings as $setting) {
        $found = false;
        foreach ($settings[$group] as $db_setting) {
            if ($db_setting['setting_key'] === $setting['setting_key']) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $settings[$group][] = $setting;
        }
    }
}

// List of common SMTP providers
$smtp_providers = [
    'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls'],
    'yahoo' => ['host' => 'smtp.mail.yahoo.com', 'port' => 465, 'secure' => 'ssl'],
    'outlook' => ['host' => 'smtp.office365.com', 'port' => 587, 'secure' => 'tls'],
    'zoho' => ['host' => 'smtp.zoho.com', 'port' => 465, 'secure' => 'ssl']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .settings-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        .settings-tab.active {
            border-color: #ddd;
            border-bottom-color: white;
            background: white;
            margin-bottom: -1px;
        }
        .settings-tab-content {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 0 5px 5px 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .settings-tab-content.active {
            display: block;
        }
        .setting-group {
            margin-bottom: 30px;
        }
        .setting-group h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .setting-item {
            margin-bottom: 15px;
        }
        .setting-item label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .setting-item input[type="text"],
        .setting-item input[type="email"],
        .setting-item input[type="number"],
        .setting-item input[type="password"],
        .setting-item select,
        .setting-item textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .file-preview {
            max-width: 200px;
            max-height: 100px;
            display: block;
            margin-top: 10px;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
    <div class="setting-group">
    <h3>Admin Menu Configuration</h3>
    <div class="setting-item">
        <label for="admin_menu">Menu Items (JSON format):</label>
        <textarea id="admin_menu" name="settings[admin_menu]" rows="10" 
                  style="font-family: monospace;"><?php echo htmlspecialchars(getSettingValue($settings, 'general', 'admin_menu')); ?></textarea>
        <p class="help-text">Format: [{"title":"Dashboard","url":"dashboard.php","icon":"fa-home"},...]</p>
    </div>
</div>
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="settings-tabs">
                    <div class="settings-tab active" data-tab="general">General</div>
                    <div class="settings-tab" data-tab="appearance">Appearance</div>
                    <div class="settings-tab" data-tab="email">Email</div>
                </div>
                
                <div class="settings-tab-content active" id="general-tab">
                    <div class="setting-group">
                        <h3>Site Information</h3>
                        
                        <div class="setting-item">
                            <label for="site_name">Site Name</label>
                            <input type="text" id="site_name" name="settings[site_name]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'general', 'site_name')); ?>">
                        </div>
                        
                        <div class="setting-item">
                            <label for="site_email">Site Email</label>
                            <input type="email" id="site_email" name="settings[site_email]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'general', 'site_email')); ?>">
                        </div>
                        
                        <div class="setting-item">
                            <label for="timezone">Timezone</label>
                            <select id="timezone" name="settings[timezone]">
                                <?php
                                $timezones = DateTimeZone::listIdentifiers();
                                $current_tz = getSettingValue($settings, 'general', 'timezone');
                                foreach ($timezones as $tz): ?>
                                    <option value="<?php echo $tz; ?>" <?php echo $tz === $current_tz ? 'selected' : ''; ?>>
                                        <?php echo $tz; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="setting-item">
                            <label for="items_per_page">Items Per Page</label>
                            <input type="number" id="items_per_page" name="settings[items_per_page]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'general', 'items_per_page')); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="settings-tab-content" id="appearance-tab">
                    <div class="setting-group">
                        <h3>Branding</h3>
                        
                        <div class="setting-item">
                            <label for="site_logo">Site Logo</label>
                            <input type="file" id="site_logo" name="site_logo" accept="image/*">
                            <?php if ($logo = getSettingValue($settings, 'appearance', 'site_logo')): ?>
                                <img src="../assets/images/<?php echo $logo; ?>" class="file-preview">
                                <p>Current: <?php echo $logo; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="setting-item">
                            <label for="favicon">Favicon</label>
                            <input type="file" id="favicon" name="favicon" accept="image/x-icon,.ico">
                            <?php if ($favicon = getSettingValue($settings, 'appearance', 'favicon')): ?>
                                <img src="../assets/images/<?php echo $favicon; ?>" class="file-preview">
                                <p>Current: <?php echo $favicon; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="setting-item">
                            <label for="theme_color">Theme Color</label>
                            <input type="color" id="theme_color" name="settings[theme_color]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'appearance', 'theme_color')); ?>">
                            <span class="color-preview" id="color-preview" 
                                  style="background-color: <?php echo htmlspecialchars(getSettingValue($settings, 'appearance', 'theme_color')); ?>"></span>
                        </div>
                    </div>
                </div>
                
                <div class="settings-tab-content" id="email-tab">
                    <div class="setting-group">
                        <h3>SMTP Settings</h3>
                        
                        <div class="setting-item">
                            <label for="smtp_provider">SMTP Provider</label>
                            <select id="smtp_provider" name="settings[smtp_provider]" onchange="updateSMTPSettings(this.value)">
                                <option value="">Custom</option>
                                <?php foreach ($smtp_providers as $provider => $details): ?>
                                    <option value="<?php echo $provider; ?>" <?php echo getSettingValue($settings, 'email', 'smtp_provider') === $provider ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($provider); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="setting-item">
                            <label for="smtp_host">SMTP Host</label>
                            <input type="text" id="smtp_host" name="settings[smtp_host]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'email', 'smtp_host')); ?>">
                        </div>
                        
                        <div class="setting-item">
                            <label for="smtp_port">SMTP Port</label>
                            <input type="number" id="smtp_port" name="settings[smtp_port]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'email', 'smtp_port')); ?>">
                        </div>
                        
                        <div class="setting-item">
                            <label for="smtp_username">SMTP Username</label>
                            <input type="text" id="smtp_username" name="settings[smtp_username]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'email', 'smtp_username')); ?>">
                        </div>
                        
                        <div class="setting-item">
                            <label for="smtp_password">SMTP Password</label>
                            <input type="password" id="smtp_password" name="settings[smtp_password]" 
                                   value="<?php echo htmlspecialchars(getSettingValue($settings, 'email', 'smtp_password')); ?>">
                        </div>
                        
                        <div class="setting-item">
                            <label for="smtp_secure">SMTP Security</label>
                            <select id="smtp_secure" name="settings[smtp_secure]">
                                <option value="tls" <?php echo getSettingValue($settings, 'email', 'smtp_secure') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo getSettingValue($settings, 'email', 'smtp_secure') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <h3>Test Email</h3>
                        <div class="setting-item">
                            <label for="test_email">Send test email to:</label>
                            <input type="email" id="test_email" name="test_email" placeholder="Enter email address">
                            <button type="button" id="send-test-email" class="btn">Send Test Email</button>
                            <div id="test-email-result" style="margin-top: 10px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and content
                document.querySelectorAll('.settings-tab, .settings-tab-content').forEach(el => {
                    el.classList.remove('active');
                });
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
        
        // Color preview
        document.getElementById('theme_color').addEventListener('input', function() {
            document.getElementById('color-preview').style.backgroundColor = this.value;
        });
        
        // Test email
        document.getElementById('send-test-email').addEventListener('click', function() {
            const email = document.getElementById('test_email').value;
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Sending...';
            
            const resultDiv = document.getElementById('test-email-result');
            resultDiv.textContent = '';
            resultDiv.className = '';
            
            fetch('send_test_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.textContent = 'Test email sent successfully!';
                    resultDiv.className = 'success-message';
                } else {
                    resultDiv.textContent = 'Error: ' + (data.message || 'Failed to send email');
                    resultDiv.className = 'error-message';
                }
            })
            .catch(error => {
                resultDiv.textContent = 'Error: ' + error.message;
                resultDiv.className = 'error-message';
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Send Test Email';
            });
        });
        
        const smtpProviders = <?php echo json_encode($smtp_providers); ?>;
        
        function updateSMTPSettings(provider) {
            if (smtpProviders[provider]) {
                document.getElementById('smtp_host').value = smtpProviders[provider].host;
                document.getElementById('smtp_port').value = smtpProviders[provider].port;
                document.getElementById('smtp_secure').value = smtpProviders[provider].secure;
            } else {
                document.getElementById('smtp_host').value = '';
                document.getElementById('smtp_port').value = '';
                document.getElementById('smtp_secure').value = 'tls';
            }
        }
    </script>
</body>
</html>

<?php
function getSettingValue($settings, $group, $key): mixed {
    if (!isset($settings[$group])) return '';
    
    foreach ($settings[$group] as $setting) {
        if ($setting['setting_key'] === $key) {
            return $setting['setting_value'];
        }
    }
    
    return '';
}
?>