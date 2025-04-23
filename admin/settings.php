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
require_once '../includes/config.php'; // Include config to initialize $pdo
$pageTitle = 'Settings';

// Handle Academic Year CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Academic Year
    if (isset($_POST['add_academic_year'])) {
        $name = trim($_POST['year_name']);
        $start = $_POST['year_start'];
        $end = $_POST['year_end'];
        if ($name && $start && $end) {
            // Changed 'name' to 'year_name'
            $stmt = $pdo->prepare("INSERT INTO academic_years (year_name, start_date, end_date) VALUES (?, ?, ?)");
            $stmt->execute([$name, $start, $end]);
            $success = "Academic Year added!";
        }
    }
    // Edit Academic Year
    if (isset($_POST['edit_academic_year'])) {
        $id = intval($_POST['year_id']);
        $name = trim($_POST['year_name']);
        $start = $_POST['year_start'];
        $end = $_POST['year_end'];
        if ($id && $name && $start && $end) {
            // Changed 'name' to 'year_name'
            $stmt = $pdo->prepare("UPDATE academic_years SET year_name=?, start_date=?, end_date=? WHERE id=?");
            $stmt->execute([$name, $start, $end, $id]);
            $success = "Academic Year updated!";
        }
    }
    // Delete Academic Year
    if (isset($_POST['delete_academic_year'])) {
        $id = intval($_POST['year_id']);
        $stmt = $pdo->prepare("DELETE FROM academic_years WHERE id=?");
        $stmt->execute([$id]);
        $success = "Academic Year deleted!";
    }
    // Add Academic Term
    if (isset($_POST['add_academic_term'])) {
        $name = trim($_POST['term_name']);
        $start = $_POST['term_start'];
        $end = $_POST['term_end'];
        $year_id = intval($_POST['term_year_id']);
        if ($name && $start && $end && $year_id) {
            // Changed 'name' to 'term_name'
            $stmt = $pdo->prepare("INSERT INTO academic_terms (academic_year_id, term_name, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$year_id, $name, $start, $end]);
            $success = "Academic Term added!";
        }
    }
    // Edit Academic Term
    if (isset($_POST['edit_academic_term'])) {
        $id = intval($_POST['term_id']);
        $name = trim($_POST['term_name']);
        $start = $_POST['term_start'];
        $end = $_POST['term_end'];
        $year_id = intval($_POST['term_year_id']);
        if ($id && $name && $start && $end && $year_id) {
            // Changed 'name' to 'term_name'
            $stmt = $pdo->prepare("UPDATE academic_terms SET academic_year_id=?, term_name=?, start_date=?, end_date=? WHERE id=?");
            $stmt->execute([$year_id, $name, $start, $end, $id]);
            $success = "Academic Term updated!";
        }
    }
    // Delete Academic Term
    if (isset($_POST['delete_academic_term'])) {
        $id = intval($_POST['term_id']);
        $stmt = $pdo->prepare("DELETE FROM academic_terms WHERE id=?");
        $stmt->execute([$id]);
        $success = "Academic Term deleted!";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only process settings if they exist in POST
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
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
    // Prevent header errors by exiting before any output
    if (!headers_sent()) {
        header("Location: settings.php");
        exit();
    }
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
        ['setting_key' => 'site_name', 'setting_value' => 'School CRM System'],
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

// Fetch Academic Years and Terms
// Changed 'name' to 'year_name'
$years = $pdo->query("SELECT * FROM academic_years ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Academic Terms with Academic Year info
$terms = $pdo->query("
    SELECT t.*, y.year_name AS year_name
    FROM academic_terms t
    LEFT JOIN academic_years y ON t.academic_year_id = y.id
    ORDER BY t.start_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .settings-group {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
        }

        .settings-group h3 {
            font-size: 20px;
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2f5;
        }

        .settings-item {
            margin-bottom: 15px;
        }

        .settings-item label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .settings-item input, .settings-item select, .settings-item textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
        }

        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .settings-tab {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            cursor: pointer;
            background-color:rgb(65, 79, 143);
            transition: var(--transition);
        }

        .settings-tab.active {
            background-color: var(--primary-color);
            color: white;
        }

        .settings-tab-content {
            display: none;
        }

        .settings-tab-content.active {
            display: block;
        }

        .form-actions {
            margin-top: 20px;
            text-align: right;
        }

        .form-actions .btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .form-actions .btn:hover {
            background-color: var(--secondary-color);
        }

        @media (max-width: 900px) {
            .settings-group {
                padding: 12px !important;
                margin: 10px 0 !important;
            }
            .settings-tabs {
                flex-direction: column;
                gap: 6px;
            }
        }
        @media (max-width: 600px) {
            .settings-group {
                padding: 8px !important;
                margin: 6px 0 !important;
            }
            .settings-tabs {
                flex-direction: column;
                gap: 4px;
            }
            .form-actions {
                text-align: left;
            }
            .settings-tab, .form-actions .btn {
                padding: 8px 10px;
                font-size: 0.95em;
            }
            .settings-tab-content {
                padding: 0 !important;
            }
            .table {
                display: block;
                width: 100%;
                overflow-x: auto;
            }
            th, td {
                white-space: nowrap;
                font-size: 0.95em;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
            </header>

            <div class="content">
                <form method="POST">
                    <div class="settings-tabs">
                        <div class="settings-tab active" data-tab="general">General</div>
                        <div class="settings-tab" data-tab="features">Features</div>
                        <div class="settings-tab" data-tab="users">User Management</div>
                        <div class="settings-tab" data-tab="logs">System Logs</div>
                        <div class="settings-tab" data-tab="academic_years">Academic Years</div>
                        <div class="settings-tab" data-tab="academic_terms">Academic Terms</div>
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
                                <table class="table">
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
                                                <td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
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
                                        <li><?php echo htmlspecialchars($log['action'] ?? ''); ?> - <?php echo $log['created_at']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Academic Years -->
                        <div class="settings-tab-content" id="academic_years-tab">
                            <div class="settings-group">
                                <h3>Academic Years</h3>
                                <form method="post" style="margin-bottom:1em;">
                                    <input type="text" name="year_name" placeholder="Year Name (e.g. 2024/25)" required>
                                    <input type="date" name="year_start" required>
                                    <input type="date" name="year_end" required>
                                    <button type="submit" name="add_academic_year" class="btn">Add Year</button>
                                </form>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($years as $year): ?>
                                            <tr>
                                                <form method="post">
                                                    <td>
                                                        <!-- Changed 'name' to 'year_name' -->
                                                        <input type="text" name="year_name" value="<?= htmlspecialchars($year['year_name']) ?>" required>
                                                        <input type="hidden" name="year_id" value="<?= $year['id'] ?>">
                                                    </td>
                                                    <td><input type="date" name="year_start" value="<?= $year['start_date'] ?>" required></td>
                                                    <td><input type="date" name="year_end" value="<?= $year['end_date'] ?>" required></td>
                                                    <td>
                                                        <button type="submit" name="edit_academic_year" class="btn btn-secondary">Save</button>
                                                        <button type="submit" name="delete_academic_year" class="btn btn-danger" onclick="return confirm('Delete this year?')">Delete</button>
                                                    </td>
                                                </form>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Academic Terms -->
                        <div class="settings-tab-content" id="academic_terms-tab">
                            <div class="settings-group">
                                <h3>Academic Terms</h3>
                                <form method="post" style="margin-bottom:1em;">
                                    <select name="term_year_id" required>
                                        <option value="">Select Year</option>
                                        <?php foreach ($years as $year): ?>
                                            <!-- Changed 'name' to 'year_name' -->
                                            <option value="<?= $year['id'] ?>"><?= htmlspecialchars($year['year_name'] ?? '') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="term_name" placeholder="Term Name (e.g. Term 1)" required>
                                    <input type="date" name="term_start" required>
                                    <input type="date" name="term_end" required>
                                    <button type="submit" name="add_academic_term" class="btn">Add Term</button>
                                </form>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Term</th>
                                            <th>Year</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($terms as $term): ?>
                                            <tr>
                                                <form method="post">
                                                    <td>
                                                        <!-- Changed 'name' to 'term_name' -->
                                                        <input type="text" name="term_name" value="<?= htmlspecialchars($term['term_name'] ?? '') ?>" required>
                                                        <input type="hidden" name="term_id" value="<?= $term['id'] ?>">
                                                    </td>
                                                    <td>
                                                        <select name="term_year_id" required>
                                                            <?php foreach ($years as $year): ?>
                                                                <!-- Changed 'name' to 'year_name' -->
                                                                <option value="<?= $year['id'] ?>" <?= $year['id'] == $term['academic_year_id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($year['year_name'] ?? '') ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="date" name="term_start" value="<?= $term['start_date'] ?>" required></td>
                                                    <td><input type="date" name="term_end" value="<?= $term['end_date'] ?>" required></td>
                                                    <td>
                                                        <button type="submit" name="edit_academic_term" class="btn btn-secondary">Save</button>
                                                        <button type="submit" name="delete_academic_term" class="btn btn-danger" onclick="return confirm('Delete this term?')">Delete</button>
                                                    </td>
                                                </form>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Save Settings</button>
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