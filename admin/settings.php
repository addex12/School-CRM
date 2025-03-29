<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php'; // Include config to initialize $pdo

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'general')");
            $stmt->execute([$key, $value]);
        }
    }

    // Handle feature toggles
    if (isset($_POST['features'])) {
        foreach ($_POST['features'] as $feature => $status) {
            $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'features')");
            $stmt->execute([$feature, $status]);
        }
    }

    // Handle user management actions
    if (!empty($_POST['user_action']) && !empty($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        if ($_POST['user_action'] === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE users SET active = 0 WHERE id = ?");
            $stmt->execute([$userId]);
        } elseif ($_POST['user_action'] === 'activate') {
            $stmt = $pdo->prepare("UPDATE users SET active = 1 WHERE id = ?");
            $stmt->execute([$userId]);
        }
    }

    $_SESSION['success'] = "Settings updated successfully!";
    header("Location: settings.php");
    exit();
}

// Fetch settings grouped by category
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_group']][] = $row;
}

// Default settings
$default_settings = [
    'general' => [
        ['setting_key' => 'site_name', 'setting_value' => 'School Survey System'],
        ['setting_key' => 'site_email', 'setting_value' => 'admin@school.edu'],
        ['setting_key' => 'timezone', 'setting_value' => 'UTC'],
        ['setting_key' => 'items_per_page', 'setting_value' => '10']
    ],
    'features' => [
        ['setting_key' => 'enable_surveys', 'setting_value' => '1'],
        ['setting_key' => 'enable_notifications', 'setting_value' => '1'],
        ['setting_key' => 'enable_chat', 'setting_value' => '1']
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

// Fetch users for user management
$users = $pdo->query("SELECT id, username, email, active FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Fetch system logs
$logs = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .settings-group {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .settings-group h3 {
            margin-bottom: 15px;
        }
        .settings-item {
            margin-bottom: 10px;
        }
        .settings-item label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .settings-item input, .settings-item select, .settings-item textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .settings-tab {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            background-color: #f1f1f1;
        }
        .settings-tab.active {
            background-color: #3498db;
            color: #fff;
        }
        .settings-tab-content {
            display: none;
        }
        .settings-tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="container">
                <form method="POST">
                    <div class="settings-tabs">
                        <div class="settings-tab active" data-tab="general">General</div>
                        <div class="settings-tab" data-tab="features">Features</div>
                        <div class="settings-tab" data-tab="users">User Management</div>
                        <div class="settings-tab" data-tab="logs">System Logs</div>
                    </div>

                    <div class="settings-container">
                        <!-- General Settings -->
                        <div class="settings-tab-content active" id="general-tab">
                            <div class="settings-group">
                                <h3>General Settings</h3>
                                <div class="settings-item">
                                    <label for="site_name">Site Name</label>
                                    <input type="text" id="site_name" name="settings[site_name]" value="<?php echo htmlspecialchars(getSettingValue($settings, 'general', 'site_name')); ?>">
                                </div>
                                <div class="settings-item">
                                    <label for="site_email">Site Email</label>
                                    <input type="email" id="site_email" name="settings[site_email]" value="<?php echo htmlspecialchars(getSettingValue($settings, 'general', 'site_email')); ?>">
                                </div>
                                <div class="settings-item">
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
                                <div class="settings-item">
                                    <label for="items_per_page">Items Per Page</label>
                                    <input type="number" id="items_per_page" name="settings[items_per_page]" value="<?php echo htmlspecialchars(getSettingValue($settings, 'general', 'items_per_page')); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Feature Toggles -->
                        <div class="settings-tab-content" id="features-tab">
                            <div class="settings-group">
                                <h3>Feature Toggles</h3>
                                <div class="settings-item">
                                    <label for="enable_surveys">Enable Surveys</label>
                                    <select id="enable_surveys" name="features[enable_surveys]">
                                        <option value="1" <?php echo getSettingValue($settings, 'features', 'enable_surveys') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="0" <?php echo getSettingValue($settings, 'features', 'enable_surveys') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div class="settings-item">
                                    <label for="enable_notifications">Enable Notifications</label>
                                    <select id="enable_notifications" name="features[enable_notifications]">
                                        <option value="1" <?php echo getSettingValue($settings, 'features', 'enable_notifications') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="0" <?php echo getSettingValue($settings, 'features', 'enable_notifications') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                                <div class="settings-item">
                                    <label for="enable_chat">Enable Chat</label>
                                    <select id="enable_chat" name="features[enable_chat]">
                                        <option value="1" <?php echo getSettingValue($settings, 'features', 'enable_chat') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                        <option value="0" <?php echo getSettingValue($settings, 'features', 'enable_chat') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- User Management -->
                        <div class="settings-tab-content" id="users-tab">
                            <div class="settings-group">
                                <h3>User Management</h3>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo $user['active'] ? 'Active' : 'Inactive'; ?></td>
                                                <td>
                                                    <button type="submit" name="user_action" value="deactivate" <?php echo !$user['active'] ? 'disabled' : ''; ?>>Deactivate</button>
                                                    <button type="submit" name="user_action" value="activate" <?php echo $user['active'] ? 'disabled' : ''; ?>>Activate</button>
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- System Logs -->
                        <div class="settings-tab-content" id="logs-tab">
                            <div class="settings-group">
                                <h3>System Logs</h3>
                                <ul>
                                    <?php foreach ($logs as $log): ?>
                                        <li><?php echo htmlspecialchars($log['action']); ?> - <?php echo $log['created_at']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.settings-tab, .settings-tab-content').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(`${this.getAttribute('data-tab')}-tab`).classList.add('active');
            });
        });
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