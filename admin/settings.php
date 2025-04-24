<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "System Settings";

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
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

// Define settings fields for School CRM (no education/curriculum/academic year)
$settings_fields = [
    'general' => [
        'site_name' => ['label' => 'Site Name', 'type' => 'text'],
        'admin_email' => ['label' => 'Admin Email', 'type' => 'email'],
        'timezone' => ['label' => 'Timezone', 'type' => 'text'],
        'language' => ['label' => 'Default Language', 'type' => 'text'],
    ],
    'email' => [
        'smtp_host' => ['label' => 'SMTP Host', 'type' => 'text'],
        'smtp_port' => ['label' => 'SMTP Port', 'type' => 'number'],
        'smtp_user' => ['label' => 'SMTP Username', 'type' => 'text'],
        'smtp_pass' => ['label' => 'SMTP Password', 'type' => 'password'],
        'smtp_secure' => ['label' => 'SMTP Secure (ssl/tls)', 'type' => 'text'],
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
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            max-width: 700px;
        }
        .erp-card h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.2rem;
        }
        .form-group {
            margin-bottom: 1.3rem;
        }
        label {
            font-weight: 600;
            color: #215967;
            margin-bottom: 6px;
            display: block;
        }
        input[type="text"], input[type="email"], input[type="number"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
        }
        input[type="checkbox"] {
            accent-color: #2563eb;
            margin-right: 6px;
        }
        .erpnext-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s;
            margin-bottom: 1rem;
        }
        .erpnext-btn:hover { background: #215967; }
        .settings-section { margin-bottom: 2.5rem; }
        @media (max-width: 900px) {
            .admin-main { margin-left: 70px; padding: 1rem; }
            .erp-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .admin-main { margin-left: 0; padding: 0.5rem; }
            .erp-card { padding: 0.7rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 style="color:#215967;font-weight:700;"><i class="fas fa-cogs"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="erp-card">
                <?php include 'includes/alerts.php'; ?>
                <form method="POST" autocomplete="off">
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
                <ul style="list-style:none;padding:0;">
                    <li><a href="user_roles.php" class="erpnext-btn" style="margin-bottom:8px;"><i class="fas fa-user-tag"></i> Manage User Roles</a></li>
                    <li><a href="manage_roles.php" class="erpnext-btn" style="margin-bottom:8px;"><i class="fas fa-user-shield"></i> Manage Role Permissions</a></li>
                    <li><a href="user_permissions.php" class="erpnext-btn" style="margin-bottom:8px;"><i class="fas fa-user-lock"></i> User Permissions</a></li>
                    <li><a href="backup.php" class="erpnext-btn" style="margin-bottom:8px;"><i class="fas fa-database"></i> Backup & Restore</a></li>
                    <li><a href="audit_log.php" class="erpnext-btn" style="margin-bottom:8px;"><i class="fas fa-history"></i> Audit Trail</a></li>
                    <li><a href="system_logs.php" class="erpnext-btn"><i class="fas fa-file-alt"></i> System Logs</a></li>
                </ul>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>