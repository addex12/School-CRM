<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer

// Secure session start
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize SMTP providers
$smtp_providers = [
    'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls'],
    'yahoo' => ['host' => 'smtp.mail.yahoo.com', 'port' => 465, 'secure' => 'ssl'],
    'outlook' => ['host' => 'smtp.office365.com', 'port' => 587, 'secure' => 'tls'],
    'zoho' => ['host' => 'smtp.zoho.com', 'port' => 465, 'secure' => 'ssl']
];
$smtp_json = json_encode($smtp_providers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

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
                
                // Validation
                switch ($key) {
                    case 'site_email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Invalid email format");
                        }
                        break;
                    case 'items_per_page':
                    case 'login_attempts':
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
        function handleUpload($field, $allowedTypes, $maxSize = 2 * 1024 * 1024) {
            global $pdo;
            if (empty($_FILES[$field]['name'])) return;
            
            $file = $_FILES[$field];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload error: " . $file['error']);
            }
            
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes)) {
                throw new Exception("Invalid file type for $field");
            }
            
            if ($file['size'] > $maxSize) {
                throw new Exception("File too large for $field");
            }
            
            $filename = uniqid() . '.' . $ext;
            $path = __DIR__ . '/../assets/uploads/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$field, $filename]);
                return $filename;
            }
            
            throw new Exception("Failed to save $field");
        }
        
        handleUpload('site_logo', ['png', 'jpg', 'jpeg', 'gif']);
        handleUpload('favicon', ['ico', 'png']);
        handleUpload('og_image', ['png', 'jpg', 'jpeg']);

        $_SESSION['success'] = "Settings updated successfully!";
        header("Location: settings.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default settings
$defaultSettings = [
    'site_name' => 'School Survey System',
    'site_email' => 'admin@school.edu',
    'timezone' => 'UTC',
    'items_per_page' => 10,
    'theme_color' => '#3498db',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_secure' => 'tls'
];

// Merge with database values
foreach ($defaultSettings as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="settings-main">
            <h1><i class="fas fa-cog"></i> System Settings</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="settings-tabs">
                    <button type="button" class="tab-btn active" data-tab="general">General</button>
                    <button type="button" class="tab-btn" data-tab="appearance">Appearance</button>
                    <button type="button" class="tab-btn" data-tab="email">Email</button>
                    <button type="button" class="tab-btn" data-tab="security">Security</button>
                </div>

                <!-- General Settings -->
                <div class="tab-content active" id="general-tab">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="settings[site_name]" value="<?= htmlspecialchars($settings['site_name']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Site Email</label>
                        <input type="email" name="settings[site_email]" value="<?= htmlspecialchars($settings['site_email']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Timezone</label>
                        <select name="settings[timezone]">
                            <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                                <option value="<?= $tz ?>" <?= $settings['timezone'] === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Items Per Page</label>
                        <input type="number" name="settings[items_per_page]" min="1" max="100" value="<?= htmlspecialchars($settings['items_per_page']) ?>">
                    </div>
                </div>

                <!-- Appearance Settings -->
                <div class="tab-content" id="appearance-tab">
                    <div class="form-group">
                        <label>Theme Color</label>
                        <input type="color" name="settings[theme_color]" value="<?= htmlspecialchars($settings['theme_color']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Site Logo</label>
                        <input type="file" name="site_logo" accept="image/*">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <div class="current-file">
                                Current: <a href="../assets/uploads/<?= $settings['site_logo'] ?>" target="_blank"><?= $settings['site_logo'] ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Favicon</label>
                        <input type="file" name="favicon" accept=".ico,image/x-icon">
                        <?php if (!empty($settings['favicon'])): ?>
                            <div class="current-file">
                                Current: <a href="../assets/uploads/<?= $settings['favicon'] ?>" target="_blank"><?= $settings['favicon'] ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="tab-content" id="email-tab">
                    <div class="form-group">
                        <label>SMTP Provider</label>
                        <select id="smtp_provider" onchange="updateSMTPSettings(this.value)">
                            <option value="">Custom</option>
                            <?php foreach ($smtp_providers as $name => $config): ?>
                                <option value="<?= $name ?>" <?= $settings['smtp_host'] === $config['host'] ? 'selected' : '' ?>>
                                    <?= ucfirst($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" id="smtp_host" name="settings[smtp_host]" value="<?= htmlspecialchars($settings['smtp_host']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="number" id="smtp_port" name="settings[smtp_port]" value="<?= htmlspecialchars($settings['smtp_port']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" id="smtp_username" name="settings[smtp_username]" value="<?= htmlspecialchars($settings['smtp_username']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" id="smtp_password" name="settings[smtp_password]" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Security</label>
                        <select id="smtp_secure" name="settings[smtp_secure]">
                            <option value="tls" <?= $settings['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $settings['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Email</label>
                        <div class="test-email">
                            <input type="email" id="test_email" placeholder="recipient@example.com">
                            <button type="button" id="send-test-email" class="btn">
                                <i class="fas fa-paper-plane"></i> Send Test
                            </button>
                            <div id="test-email-result"></div>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
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

    // SMTP Providers configuration
    const smtpProviders = <?= $smtp_json ?>;
    
    function updateSMTPSettings(provider) {
        if (smtpProviders[provider]) {
            document.getElementById('smtp_host').value = smtpProviders[provider].host;
            document.getElementById('smtp_port').value = smtpProviders[provider].port;
            document.getElementById('smtp_secure').value = smtpProviders[provider].secure;
        }
    }

    // Test email functionality
    document.getElementById('send-test-email').addEventListener('click', async function() {
        const email = document.getElementById('test_email').value;
        const resultDiv = document.getElementById('test-email-result');
        const btn = this;
        
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showResult('Please enter a valid email address', 'error');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        try {
            const response = await fetch('../includes/send_test_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'
                },
                body: JSON.stringify({
                    email: email,
                    smtp: {
                        host: document.getElementById('smtp_host').value,
                        port: document.getElementById('smtp_port').value,
                        secure: document.getElementById('smtp_secure').value,
                        username: document.getElementById('smtp_username').value,
                        password: document.getElementById('smtp_password').value
                    }
                })
            });
            
            const data = await response.json();
            showResult(data.message, data.success ? 'success' : 'error');
        } catch (error) {
            showResult('Network error: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Test';
        }
    });
    
    function showResult(message, type) {
        const div = document.getElementById('test-email-result');
        div.textContent = message;
        div.className = `alert alert-${type}`;
        div.style.display = 'block';
        
        setTimeout(() => div.style.display = 'none', 5000);
    }
    </script>
</body>
</html>