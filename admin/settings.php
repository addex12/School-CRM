<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php'; // Include config to initialize $pdo

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
    // Get system setting value
    function getSystemSetting($key, $default = null) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['value'] : $default;
        } catch (PDOException $e) {
            error_log("Settings Error: " . $e->getMessage());
            return $default;
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

    // Handle role management
    if (isset($_POST['roles'])) {
        foreach ($_POST['roles'] as $role_id => $role_name) {
            $stmt = $pdo->prepare("UPDATE roles SET role_name = ? WHERE id = ?");
            $stmt->execute([$role_name, $role_id]);
        }
    }

    // Handle system notifications
    if (!empty($_POST['notification_message'])) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (NULL, ?)");
        $stmt->execute([$_POST['notification_message']]);
    }

    // Handle advanced email testing
    if (!empty($_POST['test_email_advanced'])) {
        // Simulate sending an email (actual implementation depends on your email library)
        $_SESSION['success'] = "Advanced test email sent to {$_POST['test_email_advanced']}!";
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

// Fetch roles for role management
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);

// Fetch notifications for display
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
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
                            <div class="settings-tab" data-tab="roles">Roles</div>
                            <div class="settings-tab" data-tab="notifications">Notifications</div>
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

                            <div class="setting-group">
                                <h3>Advanced Email Testing</h3>
                                <div class="setting-item">
                                    <label for="test_email_advanced">Send advanced test email to:</label>
                                    <input type="email" id="test_email_advanced" name="test_email_advanced" placeholder="Enter email address">
                                    <button type="submit" class="btn">Send Advanced Test Email</button>
                                </div>
                            </div>
                        </div>

                        <div class="settings-tab-content" id="roles-tab">
                            <div class="setting-group">
                                <h3>Manage Roles</h3>
                                <?php foreach ($roles as $role): ?>
                                    <div class="setting-item">
                                        <label for="role_<?php echo $role['id']; ?>">Role Name:</label>
                                        <input type="text" id="role_<?php echo $role['id']; ?>" name="roles[<?php echo $role['id']; ?>]" value="<?php echo htmlspecialchars($role['role_name']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="settings-tab-content" id="notifications-tab">
                            <div class="setting-group">
                                <h3>Send System Notification</h3>
                                <div class="setting-item">
                                    <label for="notification_message">Notification Message:</label>
                                    <textarea id="notification_message" name="notification_message" rows="4"></textarea>
                                    <button type="submit" class="btn">Send Notification</button>
                                </div>
                            </div>
                            <div class="setting-group">
                                <h3>Recent Notifications</h3>
                                <ul>
                                    <?php foreach ($notifications as $notification): ?>
                                        <li><?php echo htmlspecialchars($notification['message']); ?> - <small><?php echo $notification['created_at']; ?></small></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
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