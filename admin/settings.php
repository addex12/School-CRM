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
        .adugna-sidebar {
            width: 220px;
            background: #232f3e;
            color: #fff;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            transition: transform 0.3s;
            box-shadow: 2px 0 8px rgba(0,0,0,0.04);
        }
        .adugna-sidebar.adugna-closed {
            transform: translateX(-100%);
        }
        .adugna-sidebar .adugna-logo {
            font-size: 1.1rem;
            font-weight: 700;
            padding: 1rem 1.2rem;
            letter-spacing: 1px;
            background: #1a222c;
            margin-bottom: 0.5rem;
        }
        .adugna-sidebar ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .adugna-sidebar li {
            position: relative;
        }
        .adugna-sidebar a {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.2rem;
            color: #cfd8dc;
            font-size: 0.93rem;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .adugna-sidebar a.adugna-active, .adugna-sidebar a:hover {
            background: #1a222c;
            color: #fff;
            border-left: 3px solid #3498db;
        }
        .adugna-sidebar .adugna-icon {
            font-size: 1rem;
            margin-right: 0.7rem;
            width: 1.2rem;
            text-align: center;
        }
        .adugna-sidebar .adugna-submenu-toggle {
            margin-left: auto;
            font-size: 0.8rem;
            transition: transform 0.2s;
        }
        .adugna-sidebar .adugna-submenu {
            display: none;
            background: #25344a;
        }
        .adugna-sidebar .adugna-submenu.adugna-open {
            display: block;
        }
        .adugna-sidebar .adugna-submenu a {
            padding-left: 2.2rem;
            font-size: 0.89rem;
        }
        .adugna-sidebar .adugna-submenu-toggle.adugna-rotated {
            transform: rotate(90deg);
        }
        .adugna-sidebar-toggle-btn {
            display: none;
            position: fixed;
            left: 1rem;
            top: 1rem;
            background: #232f3e;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 2.2rem;
            height: 2.2rem;
            z-index: 200;
            font-size: 1.2rem;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            cursor: pointer;
        }
        /* Adugna Gizaw: Responsive layout for admin dashboard */
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
            .adugna-sidebar {
                position: fixed;
                height: 100vh;
                z-index: 100;
            }
            .adugna-sidebar-toggle-btn {
                display: flex;
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
    <!-- Adugna Gizaw: Sidebar toggle button for mobile -->
    <button class="adugna-sidebar-toggle-btn" id="adugnaSidebarToggle" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <div class="adugna-admin-dashboard">
        <!-- Adugna Gizaw: Sidebar with submenu logic (do not touch Admin_sidebar or footer CSS) -->
        <nav class="adugna-sidebar" id="adugnaSidebar">
            <div class="adugna-logo">
                <i class="fas fa-school"></i> School CRM
            </div>
            <ul>
                <li>
                    <a href="dashboard.php" class="adugna-sidebar-link" data-page="dashboard.php">
                        <span class="adugna-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
                    </a>
                </li>
                <li>
                    <a href="backup.php" class="adugna-sidebar-link" data-page="backup.php">
                        <span class="adugna-icon"><i class="fas fa-database"></i></span> Backup
                    </a>
                </li>
                <li>
                    <a href="restore.php" class="adugna-sidebar-link" data-page="restore.php">
                        <span class="adugna-icon"><i class="fas fa-upload"></i></span> Restore
                    </a>
                </li>
                <li>
                    <a href="#" class="adugna-sidebar-link adugna-has-submenu" data-submenu="settings">
                        <span class="adugna-icon"><i class="fas fa-cogs"></i></span> Settings
                        <span class="adugna-submenu-toggle"><i class="fas fa-chevron-right"></i></span>
                    </a>
                    <ul class="adugna-submenu" data-submenu="settings">
                        <li>
                            <a href="users.php" class="adugna-sidebar-link" data-page="users.php">
                                <span class="adugna-icon"><i class="fas fa-users"></i></span> Users
                            </a>
                        </li>
                        <li>
                            <a href="roles.php" class="adugna-sidebar-link" data-page="roles.php">
                                <span class="adugna-icon"><i class="fas fa-user-shield"></i></span> Roles
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" class="adugna-sidebar-link" data-page="settings.php">
                                <span class="adugna-icon"><i class="fas fa-cogs"></i></span> System Settings
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
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
    /**
     * Adugna Gizaw: Sidebar toggle, submenu logic, active page highlight, and responsive sidebar.
     */
    (function() {
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('adugnaSidebar');
        const toggleBtn = document.getElementById('adugnaSidebarToggle');
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('adugna-closed');
        });

        // Keep sidebar open on desktop, close on mobile navigation
        function handleSidebarOnResize() {
            if (window.innerWidth > 900) {
                sidebar.classList.remove('adugna-closed');
            }
        }
        window.addEventListener('resize', handleSidebarOnResize);
        handleSidebarOnResize();

        // Submenu logic
        document.querySelectorAll('.adugna-has-submenu').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const submenuName = link.getAttribute('data-submenu');
                const submenu = document.querySelector('.adugna-submenu[data-submenu="' + submenuName + '"]');
                const toggleIcon = link.querySelector('.adugna-submenu-toggle');
                submenu.classList.toggle('adugna-open');
                toggleIcon.classList.toggle('adugna-rotated');
            });
        });

        // Highlight active page
        const currentPage = location.pathname.split('/').pop();
        document.querySelectorAll('.adugna-sidebar-link[data-page]').forEach(function(link) {
            if (link.getAttribute('data-page') === currentPage) {
                link.classList.add('adugna-active');
                // Open parent submenu if inside submenu
                const submenu = link.closest('.adugna-submenu');
                if (submenu) {
                    submenu.classList.add('adugna-open');
                    const parentToggle = submenu.parentElement.querySelector('.adugna-submenu-toggle');
                    if (parentToggle) parentToggle.classList.add('adugna-rotated');
                }
            }
        });

        // Close sidebar on mobile after navigation
        document.querySelectorAll('.adugna-sidebar-link[data-page]').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 900) {
                    sidebar.classList.add('adugna-closed');
                }
            });
        });
    })();
    </script>
</body>
</html>