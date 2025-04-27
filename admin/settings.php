<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "System Settings";

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        // Handle file upload for site logo
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $targetFile = $uploadDir . 'logo.png'; // Always save as logo.png
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $targetFile)) {
                $_POST['settings']['site_logo'] = $targetFile;
            } else {
                $_SESSION['error'] = "Failed to upload site logo.";
            }
        }

        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$key, $value]);
        }
        $_SESSION['success'] = "Settings updated successfully!";
        header("Location: settings.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update settings: " . $e->getMessage();
    }
}

// Fetch all settings
$stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Define settings fields for School CRM (add site logo, cards, etc.)
$settings_fields = [
    'general' => [
        'site_name' => ['label' => 'Site Name', 'type' => 'text'],
        'site_logo' => ['label' => 'Site Logo', 'type' => 'file'],
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
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 1.2rem 0.5rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.2rem 1.2rem;
            margin-bottom: 1.2rem;
            max-width: 700px;
            border: 1px solid #e5e7eb;
        }
        .erp-card h2 {
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 0.9rem;
            font-size: 1.1rem;
            letter-spacing: 0.01em;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            font-weight: 600;
            color: #215967;
            margin-bottom: 4px;
            display: block;
            font-size: 0.97em;
        }
        input[type="text"], input[type="email"], input[type="number"], input[type="password"] {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #f9fafb;
            font-size: 0.97em;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="number"]:focus, input[type="password"]:focus {
            border: 1.5px solid #2563eb;
            outline: none;
            background: #fff;
        }
        input[type="checkbox"] {
            accent-color: #2563eb;
            margin-right: 4px;
            transform: scale(1.07);
        }
        .erpnext-btn {
            background: linear-gradient(90deg, #2563eb 0%, #215967 100%);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.35rem 0.9rem;
            font-size: 0.97em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            margin-bottom: 0.7rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
        }
        .erpnext-btn i {
            font-size: 0.97em;
        }
        .erpnext-btn:hover, .erpnext-btn:focus {
            background: linear-gradient(90deg, #215967 0%, #2563eb 100%);
            box-shadow: 0 2px 8px rgba(44,62,80,0.12);
        }
        .settings-section { margin-bottom: 1.5rem; }
        .admin-header {
            margin-bottom: 1.2rem;
            border-bottom: 1.5px solid #e5e7eb;
            padding-bottom: 0.7rem;
        }
        .admin-header h1 {
            color: #2563eb;
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: 0.01em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .admin-header i {
            font-size: 1.1em;
        }
        .admin-tools-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .admin-tools-list li {
            margin-bottom: 0.3rem;
        }
        .admin-tools-list .erpnext-btn {
            font-size: 0.95em;
            padding: 0.3rem 0.7rem;
            gap: 0.3em;
        }
        .admin-tools-list i {
            font-size: 0.95em;
        }
        @media (max-width: 900px) {
            .admin-main { margin-left: 70px; padding: 0.7rem 0.3rem; }
            .erp-card { padding: 0.7rem; }
        }
        @media (max-width: 600px) {
            .admin-main { margin-left: 0; padding: 0.3rem; }
            .erp-card { padding: 0.4rem; }
            .admin-header h1 { font-size: 1rem; }
            .erp-card h2 { font-size: 1em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-cogs"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="erp-card">
                <?php include 'includes/alerts.php'; ?>
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="update_settings" value="1">
                    <?php foreach ($settings_fields as $section => $fields): ?>
                        <div class="settings-section">
                            <h2><?= ucfirst($section) ?> Settings</h2>
                            <?php foreach ($fields as $key => $field): ?>
                                <div class="form-group">
                                    <label for="<?= $key ?>"><?= $field['label'] ?></label>
                                    <?php if ($field['type'] === 'checkbox'): ?>
                                        <input type="checkbox" id="<?= $key ?>" name="settings[<?= $key ?>]" value="1"
                                            <?= !empty($settings[$key]) && $settings[$key] == '1' ? 'checked' : '' ?>>
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
                                        <?php if (!empty($settings[$key])): ?>
                                            <p>Current Logo: <img src="<?= htmlspecialchars($settings[$key]) ?>" alt="Site Logo" style="height: 50px;"></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <input type="<?= $field['type'] ?>" id="<?= $key ?>" name="settings[<?= $key ?>]"
                                            value="<?= htmlspecialchars($settings[$key] ?? '') ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="erpnext-btn"><i class="fas fa-save"></i> Save Settings</button>
                </form>
            </div>
            <div class="erp-card">
                <h2>Other Admin Tools</h2>
                <ul class="admin-tools-list">
                    <li><a href="user_roles.php" class="erpnext-btn"><i class="fas fa-user-tag"></i> Roles</a></li>
                    <li><a href="manage_roles.php" class="erpnext-btn"><i class="fas fa-user-shield"></i> Permissions</a></li>
                    <li><a href="user_permissions.php" class="erpnext-btn"><i class="fas fa-user-lock"></i> User Perms</a></li>
                    <li><a href="backup.php" class="erpnext-btn"><i class="fas fa-database"></i> Backup</a></li>
                    <li><a href="audit_log.php" class="erpnext-btn"><i class="fas fa-history"></i> Audit</a></li>
                    <li><a href="system_logs.php" class="erpnext-btn"><i class="fas fa-file-alt"></i> Logs</a></li>
                </ul>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>