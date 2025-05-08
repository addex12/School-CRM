<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "System Settings";

// Clear unrelated session messages to avoid showing survey messages here
unset($_SESSION['survey_success'], $_SESSION['survey_error']);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        // Always overwrite the image if a new file is uploaded
        // Handle logo.png
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $targetFile = $uploadDir . 'logo.png';
            // Remove old file if exists
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            move_uploaded_file($_FILES['site_logo']['tmp_name'], $targetFile);
            $_POST['settings']['site_logo'] = $targetFile;
        }
        // Handle bg.png
        if (isset($_FILES['login_bg_image']) && $_FILES['login_bg_image']['error'] === UPLOAD_ERR_OK) {
            $targetFile = $uploadDir . 'bg.png';
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            move_uploaded_file($_FILES['login_bg_image']['tmp_name'], $targetFile);
            $_POST['settings']['login_bg_image'] = $targetFile;
        }
        // Handle banner.png
        if (isset($_FILES['site_banner']) && $_FILES['site_banner']['error'] === UPLOAD_ERR_OK) {
            $targetFile = $uploadDir . 'banner.png';
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            move_uploaded_file($_FILES['site_banner']['tmp_name'], $targetFile);
            $_POST['settings']['site_banner'] = $targetFile;
        }
        // Handle icon.png
        if (isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] === UPLOAD_ERR_OK) {
            $targetFile = $uploadDir . 'icon.png';
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            move_uploaded_file($_FILES['site_icon']['tmp_name'], $targetFile);
            $_POST['settings']['site_icon'] = $targetFile;
        }

        // --- FIX: Ensure unchecked checkboxes are saved as '0' ---
        // List all checkbox fields here
        $checkboxFields = ['allow_user_registration'];
        foreach ($checkboxFields as $cb) {
            if (!isset($_POST['settings'][$cb])) {
                $_POST['settings'][$cb] = '0';
            }
        }

        // Save all settings to DB
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$key, $value]);
        }
        // Force browser to reload images by appending a query string (cache busting)
        clearstatcache(true, $uploadDir . 'logo.png');
        clearstatcache(true, $uploadDir . 'bg.png');
        clearstatcache(true, $uploadDir . 'banner.png');
        clearstatcache(true, $uploadDir . 'icon.png');

        $_SESSION['success'] = "Settings updated successfully!";
        header("Location: settings.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update settings: " . $e->getMessage();
    }
}

// --- Handle multiple login background images upload (separate form) ---
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['login_bg_images']) && !empty($_FILES['login_bg_images']['name'][0])
) {
    try {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $bgImages = [];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        foreach ($_FILES['login_bg_images']['tmp_name'] as $idx => $tmpName) {
            if ($_FILES['login_bg_images']['error'][$idx] === UPLOAD_ERR_OK) {
                $fileType = mime_content_type($tmpName);
                if (in_array($fileType, $allowedTypes)) {
                    $ext = pathinfo($_FILES['login_bg_images']['name'][$idx], PATHINFO_EXTENSION);
                    $fileName = 'bg_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $targetFile = $uploadDir . $fileName;
                    move_uploaded_file($tmpName, $targetFile);
                    $bgImages[] = $fileName;
                }
            }
        }
        // Merge with existing images if any
        $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'login_bg_images'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing = [];
        if ($row && !empty($row['setting_value'])) {
            $existing = json_decode($row['setting_value'], true) ?: [];
        }
        $allImages = array_merge($existing, $bgImages);
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['login_bg_images', json_encode($allImages)]);
        $_SESSION['success'] = "Background images uploaded successfully!";
        header("Location: settings.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to upload background images: " . $e->getMessage();
    }
}

// --- Handle removal of a login background image ---
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_bg_image']) && !empty($_POST['remove_bg_image'])
) {
    try {
        $imgToRemove = $_POST['remove_bg_image'];
        $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'login_bg_images'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing = [];
        if ($row && !empty($row['setting_value'])) {
            $existing = json_decode($row['setting_value'], true) ?: [];
        }
        // Remove the image from the array
        $updated = array_values(array_filter($existing, function($img) use ($imgToRemove) {
            return $img !== $imgToRemove;
        }));
        // Update DB
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'login_bg_images'");
        $stmt->execute([json_encode($updated)]);
        // Delete the file
        $filePath = '../uploads/' . $imgToRemove;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $_SESSION['success'] = "Background image removed successfully!";
        header("Location: settings.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to remove background image: " . $e->getMessage();
    }
}

// Handle send test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_email'])) {
    $testEmail = trim($_POST['test_email'] ?? '');
    if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            // Fetch SMTP settings from DB
            $smtp_host = $settings['smtp_host'] ?? '';
            $smtp_port = $settings['smtp_port'] ?? 587;
            $smtp_user = $settings['smtp_user'] ?? '';
            $smtp_pass = $settings['smtp_pass'] ?? '';
            $smtp_secure = $settings['smtp_secure'] ?? '';
            $from_email = $settings['from_email'] ?? $smtp_user;

            // Use PHPMailer for sending test email
            require_once '../vendor/autoload.php';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->Port = $smtp_port;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            if ($smtp_secure) {
                $mail->SMTPSecure = $smtp_secure;
            }
            $mail->setFrom($from_email, 'School CRM');
            $mail->addAddress($testEmail);
            $mail->Subject = 'Test Email from School CRM';
            $mail->Body = 'This is a test email sent from your School CRM settings page.';
            $mail->send();
            $_SESSION['success'] = "Test email sent successfully to $testEmail!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to send test email: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid test email address.";
    }
    header("Location: settings.php");
    exit();
}

// Fetch all settings
$stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Add all image fields with fixed names
$settings_fields = [
    'general' => [
        'site_name' => ['label' => 'Site Name', 'type' => 'text'],
        'site_logo' => ['label' => 'Site Logo (logo.png)', 'type' => 'file'],
        'site_banner' => ['label' => 'Site Banner (banner.png)', 'type' => 'file'],
        'site_icon' => ['label' => 'Site Icon (icon.png)', 'type' => 'file'],
        'login_bg_image' => ['label' => 'Login Background (bg.png)', 'type' => 'file'],
        'admin_email' => ['label' => 'Admin Email', 'type' => 'email'],
        'timezone' => ['label' => 'Timezone', 'type' => 'text'],
        'language' => ['label' => 'Default Language', 'type' => 'text'],
        'dashboard_cards' => ['label' => 'Dashboard Cards (comma separated)', 'type' => 'text'],
    ],
    'email' => [
        'smtp_host' => [
            'label' => 'SMTP Host',
            'type' => 'select',
            'options' => [
                '' => 'Select SMTP Provider',
                'smtp.gmail.com' => 'Gmail',
                'smtp.mail.yahoo.com' => 'Yahoo',
                'smtp.office365.com' => 'Office 365',
                'smtp.mailgun.org' => 'Mailgun',
                'smtp.sendgrid.net' => 'SendGrid',
            ]
        ],
        'smtp_port' => ['label' => 'SMTP Port', 'type' => 'number'],
        'smtp_user' => ['label' => 'SMTP Username', 'type' => 'text'],
        'smtp_pass' => ['label' => 'SMTP Password', 'type' => 'password'],
        'smtp_secure' => [
            'label' => 'SMTP Secure',
            'type' => 'select',
            'options' => [
                '' => 'Select Security',
                'ssl' => 'SSL',
                'tls' => 'TLS',
            ]
        ],
        'from_email' => ['label' => 'From Email', 'type' => 'email'],
    ],
    'security' => [
        'password_min_length' => ['label' => 'Password Min Length', 'type' => 'number'],
        'session_timeout' => ['label' => 'Session Timeout (minutes)', 'type' => 'number'],
        'allow_user_registration' => ['label' => 'Allow User Registration', 'type' => 'checkbox'],
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!--
        Developer: Adugna Gizaw
        Email: gizawadugna@gmail.com
        LinkedIn: https://www.linkedin.com/in/eleganceict
        Twitter: https://twitter.com/eleganceict1
        GitHub: https://github.com/addex12
        Purpose: System Settings page for School CRM, all custom styles use adugna- prefix for patenting.
    -->
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting and clarity */
        .adugna-admin-dashboard {
            display: flex;
            min-height: 100vh;
            background: var(--adugna-light, #f4f6fa);
        }
        .adugna-admin-main {
            flex: 1;
            margin-left: 220px;
            padding: 2.2rem 1.2rem 1.2rem 1.2rem;
            transition: margin-left 0.3s;
        }
        @media (max-width: 900px) {
            .adugna-admin-main {
                margin-left: 0;
            }
        }
        /* Adugna Gizaw: Color variables for consistent theming */
        :root {
            --adugna-primary: #4f46e5;
            --adugna-primary-dark: #4338ca;
            --adugna-secondary: #10b981;
            --adugna-danger: #ef4444;
            --adugna-light: #f9fafb;
            --adugna-dark: #111827;
            --adugna-gray: #6b7280;
            --adugna-gray-light: #e5e7eb;
            --adugna-card-radius: 0.5rem;
            --adugna-transition: 0.15s cubic-bezier(.4,0,.2,1);
        }
        body {
            background: var(--adugna-light);
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
        }
        /* Adugna Gizaw: Compact card style, ERPNext-inspired */
        .adugna-card {
            background: #fff;
            border-radius: var(--adugna-card-radius);
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.1rem 1.1rem;
            margin-bottom: 1.2rem;
            max-width: 700px;
            border: 1px solid var(--adugna-gray-light);
        }
        .adugna-card h2 {
            color: var(--adugna-primary);
            font-weight: 600;
            margin-bottom: 0.8rem;
            font-size: 1.08rem;
            letter-spacing: 0.01em;
        }
        /* Adugna Gizaw: Form group styling */
        .adugna-form-group {
            margin-bottom: 0.85rem;
        }
        .adugna-form-group label {
            font-weight: 600;
            color: var(--adugna-dark);
            margin-bottom: 4px;
            display: block;
            font-size: 0.97em;
        }
        .adugna-form-group input[type="text"],
        .adugna-form-group input[type="email"],
        .adugna-form-group input[type="number"],
        .adugna-form-group input[type="password"],
        .adugna-form-group select {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid var(--adugna-gray-light);
            border-radius: 4px;
            background: #f9fafb;
            font-size: 0.97em;
            transition: border var(--adugna-transition);
        }
        .adugna-form-group input[type="text"]:focus,
        .adugna-form-group input[type="email"]:focus,
        .adugna-form-group input[type="number"]:focus,
        .adugna-form-group input[type="password"]:focus,
        .adugna-form-group select:focus {
            border: 1.5px solid var(--adugna-primary);
            outline: none;
            background: #fff;
        }
        .adugna-form-group input[type="checkbox"] {
            accent-color: var(--adugna-primary);
            margin-right: 4px;
            transform: scale(1.07);
        }
        /* Adugna Gizaw: Compact button style, ERPNext-inspired */
        .adugna-btn {
            background: linear-gradient(90deg, var(--adugna-primary) 0%, var(--adugna-primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.32rem 0.85rem;
            font-size: 0.97em;
            font-weight: 500;
            cursor: pointer;
            transition: background var(--adugna-transition), box-shadow var(--adugna-transition);
            margin-bottom: 0.7rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
            gap: 0.35em;
        }
        .adugna-btn i {
            font-size: 0.97em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, var(--adugna-primary-dark) 0%, var(--adugna-primary) 100%);
            box-shadow: 0 2px 8px rgba(44,62,80,0.12);
        }
        .adugna-settings-section { margin-bottom: 1.3rem; }
        /* Adugna Gizaw: Header styles */
        .adugna-admin-header {
            margin-bottom: 1.1rem;
            border-bottom: 1.5px solid var(--adugna-gray-light);
            padding-bottom: 0.7rem;
        }
        .adugna-admin-header h1 {
            color: var(--adugna-primary);
            font-weight: 700;
            font-size: 1.23rem;
            letter-spacing: 0.01em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.45em;
        }
        .adugna-admin-header i {
            font-size: 1.07em;
        }
        /* Adugna Gizaw: Admin tools list */
        .adugna-admin-tools-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .adugna-admin-tools-list li {
            margin-bottom: 0.2rem;
        }
        .adugna-admin-tools-list .adugna-btn {
            font-size: 0.95em;
            padding: 0.28rem 0.7rem;
            gap: 0.3em;
        }
        .adugna-admin-tools-list i {
            font-size: 0.93em;
        }
        /* Adugna Gizaw: Responsive adjustments for all screens */
        @media (max-width: 900px) {
            .adugna-admin-main { margin-left: 70px; padding: 0.7rem 0.3rem; }
            .adugna-card { padding: 0.7rem; }
        }
        @media (max-width: 600px) {
            .adugna-admin-main { margin-left: 0; padding: 0.3rem; }
            .adugna-card { padding: 0.4rem; }
            .adugna-admin-header h1 { font-size: 1rem; }
            .adugna-card h2 { font-size: 1em; }
        }
    </style>
</head>
<body>
    <div class="adugna-admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-admin-main">
            <header class="adugna-admin-header">
                <h1><i class="fas fa-cogs"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="adugna-card">
                <?php include 'includes/alerts.php'; ?>
                <!-- Adugna Gizaw: Settings form, supports logo, banner, icon, and login background image upload -->
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="update_settings" value="1">
                    <?php foreach ($settings_fields as $section => $fields): ?>
                        <div class="adugna-settings-section">
                            <h2><?= ucfirst($section) ?> Settings</h2>
                            <?php foreach ($fields as $key => $field): ?>
                                <div class="adugna-form-group">
                                    <label for="<?= $key ?>"><?= $field['label'] ?></label>
                                    <?php if ($field['type'] === 'checkbox'): ?>
                                        <input type="checkbox" id="<?= $key ?>" name="settings[<?= $key ?>]" value="1"
                                            <?= !empty($settings[$key]) && $settings[$key] == '1' ? 'checked' : '' ?>>
                                    <?php elseif ($field['type'] === 'select' && $key === 'smtp_host'): ?>
                                        <select id="<?= $key ?>" name="settings[<?= $key ?>]">
                                            <?php
                                            // SMTP provider autofill mapping
                                            $smtpProviderData = [
                                                'smtp.gmail.com' => ['port' => 587, 'secure' => 'tls'],
                                                'smtp.mail.yahoo.com' => ['port' => 587, 'secure' => 'tls'],
                                                'smtp.office365.com' => ['port' => 587, 'secure' => 'tls'],
                                                'smtp.mailgun.org' => ['port' => 587, 'secure' => 'tls'],
                                                'smtp.sendgrid.net' => ['port' => 587, 'secure' => 'tls'],
                                            ];
                                            foreach ($field['options'] as $optionValue => $optionLabel):
                                                $dataAttrs = '';
                                                if (isset($smtpProviderData[$optionValue])) {
                                                    $dataAttrs = ' data-port="' . $smtpProviderData[$optionValue]['port'] . '" data-secure="' . $smtpProviderData[$optionValue]['secure'] . '"';
                                                }
                                            ?>
                                                <option value="<?= $optionValue ?>"<?= isset($settings[$key]) && $settings[$key] == $optionValue ? ' selected' : '' ?><?= $dataAttrs ?>>
                                                    <?= $optionLabel ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($field['type'] === 'select'): ?>
                                        <select id="<?= $key ?>" name="settings[<?= $key ?>]">
                                            <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                                                <option value="<?= $optionValue ?>" <?= isset($settings[$key]) && $settings[$key] == $optionValue ? 'selected' : '' ?>>
                                                    <?= $optionLabel ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($field['type'] === 'file'): ?>
                                        <input type="file" id="<?= $key ?>" name="<?= $key ?>">
                                        <?php
                                        // Show preview for each image type
                                        $imgPath = '';
                                        if ($key === 'site_logo') $imgPath = '../uploads/logo.png';
                                        if ($key === 'site_banner') $imgPath = '../uploads/banner.png';
                                        if ($key === 'site_icon') $imgPath = '../uploads/icon.png';
                                        if ($key === 'login_bg_image') $imgPath = '../uploads/bg.png';
                                        if (file_exists($imgPath)): ?>
                                            <p style="margin:0.3em 0 0 0;">
                                                <?php if ($key === 'site_logo'): ?>
                                                    Current Logo: <img src="<?= $imgPath ?>" alt="Site Logo" style="height: 38px;">
                                                <?php elseif ($key === 'site_banner'): ?>
                                                    Current Banner: <img src="<?= $imgPath ?>" alt="Site Banner" style="height: 38px;">
                                                <?php elseif ($key === 'site_icon'): ?>
                                                    Current Icon: <img src="<?= $imgPath ?>" alt="Site Icon" style="height: 24px;">
                                                <?php elseif ($key === 'login_bg_image'): ?>
                                                    Current Background: <img src="<?= $imgPath ?>" alt="Login Background" style="height: 38px;">
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <input type="<?= $field['type'] ?>" id="<?= $key ?>" name="settings[<?= $key ?>]"
                                            value="<?= htmlspecialchars($settings[$key] ?? '') ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="adugna-btn"><i class="fas fa-save"></i> Save Settings</button>
                </form>
            </div>
            <div class="adugna-card">
                <h2>Send Test Email</h2>
                <form method="POST" style="display:flex;gap:1em;align-items:center;flex-wrap:wrap;">
                    <input type="email" name="test_email" placeholder="Enter email address" required style="max-width:260px;">
                    <button type="submit" name="send_test_email" class="adugna-btn"><i class="fas fa-paper-plane"></i> Send Test Email</button>
                </form>
            </div>
            <div class="adugna-card">
                <h2>Login Background Images (Slider)</h2>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom:1em;">
                    <input type="file" name="login_bg_images[]" multiple accept="image/*">
                    <button type="submit" class="adugna-btn"><i class="fas fa-upload"></i> Upload Images</button>
                </form>
                <div style="display:flex; flex-wrap:wrap; gap:12px;">
                    <?php $bgImgs = json_decode($settings['login_bg_images'] ?? '[]', true) ?: [];
                    foreach ($bgImgs as $img): ?>
                        <div style="position:relative; display:inline-block;">
                            <img src="../uploads/<?= htmlspecialchars($img) ?>" style="height:60px; border-radius:6px; box-shadow:0 2px 8px #0002;">
                            <form method="POST" style="position:absolute;top:0;right:0;">
                                <input type="hidden" name="remove_bg_image" value="<?= htmlspecialchars($img) ?>">
                                <button type="submit" class="adugna-btn" style="padding:2px 7px;font-size:0.9em;background:#e74c3c; color:#fff; border-radius:0 6px 0 6px;">&times;</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($bgImgs)): ?>
                        <span style="color:#888;">No background images uploaded yet.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="adugna-card">
                <h2>Other Admin Tools</h2>
                <ul class="adugna-admin-tools-list">
                    <li><a href="user_roles.php" class="adugna-btn"><i class="fas fa-user-tag"></i> Roles</a></li>
                    <li><a href="manage_roles.php" class="adugna-btn"><i class="fas fa-user-shield"></i> Permissions</a></li>
                    <li><a href="user_permissions.php" class="adugna-btn"><i class="fas fa-user-lock"></i> User Perms</a></li>
                    <li><a href="backup.php" class="adugna-btn"><i class="fas fa-database"></i> Backup</a></li>
                    <li><a href="audit_log.php" class="adugna-btn"><i class="fas fa-history"></i> Audit</a></li>
                    <li><a href="system_logs.php" class="adugna-btn"><i class="fas fa-file-alt"></i> Logs</a></li>
                </ul>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
    // Autofill SMTP port and security when provider is selected
    document.addEventListener('DOMContentLoaded', function() {
        var smtpHost = document.getElementById('smtp_host');
        var smtpPort = document.getElementsByName('settings[smtp_port]')[0];
        var smtpSecure = document.getElementById('smtp_secure');
        if (smtpHost && smtpPort && smtpSecure) {
            smtpHost.addEventListener('change', function() {
                var selected = smtpHost.options[smtpHost.selectedIndex];
                var port = selected.getAttribute('data-port');
                var secure = selected.getAttribute('data-secure');
                if (port !== null && port !== '') smtpPort.value = port;
                if (secure !== null && secure !== '') smtpSecure.value = secure;
            });
        }
    });
    </script>
    <style>
        /* Adugna Gizaw: Make textboxes and textareas compact, attractive, and not too long */
        .adugna-form-group input[type="text"],
        .adugna-form-group input[type="email"],
        .adugna-form-group input[type="number"],
        .adugna-form-group input[type="password"],
        .adugna-form-group select,
        .adugna-form-group textarea {
            max-width: 420px;
            min-width: 180px;
            width: 100%;
            border-radius: 0.5em;
            border: 1.5px solid #e5e7eb;
            background: #f3f4f6;
            padding: 0.65em 1em;
            font-size: 1em;
            transition: border 0.18s, box-shadow 0.18s;
            box-sizing: border-box;
            margin-bottom: 0.1em;
        }
        .adugna-form-group textarea {
            min-height: 70px;
            resize: vertical;
            font-family: inherit;
        }
        .adugna-form-group input:focus,
        .adugna-form-group select:focus,
        .adugna-form-group textarea:focus {
            border: 1.5px solid #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 2px #a5b4fc33;
        }
        .adugna-form-group input[type="text"]:hover,
        .adugna-form-group input[type="email"]:hover,
        .adugna-form-group input[type="number"]:hover,
        .adugna-form-group input[type="password"]:hover,
        .adugna-form-group select:hover,
        .adugna-form-group textarea:hover {
            border: 1.5px solid #a5b4fc;
        }
        @media (max-width: 600px) {
            .adugna-form-group input[type="text"],
            .adugna-form-group input[type="email"],
            .adugna-form-group input[type="number"],
            .adugna-form-group input[type="password"],
            .adugna-form-group select,
            .adugna-form-group textarea {
                max-width: 100%;
                font-size: 0.97em;
                padding: 0.5em 0.7em;
            }
        }
    </style>
</body>
</html>