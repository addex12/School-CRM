<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/setting.php';

// Update user activity
$stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
requireAdmin();

// Get site settings
$siteName     = getSystemSetting('site_name', 'Survey System');
$siteLogo     = getSystemSetting('site_logo');
$themeColor   = getSystemSetting('theme_color', '#3498db');

// Get and validate admin menu configuration
$adminMenu = [];
$menuJson  = getSystemSetting('admin_menu');

if (!empty($menuJson)) {
    $adminMenu = json_decode($menuJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid admin menu JSON: " . json_last_error_msg());
        $adminMenu = [];
    }
}

// Fallback to default menu if empty or invalid
if (empty($adminMenu)) {
    $adminMenu = [
        [
            'title' => 'Dashboard',
            'url'   => 'dashboard.php',
            'icon'  => 'fa-home',
            'roles' => ['admin']
        ],
        [
            'title' => 'Surveys',
            'url'   => 'surveys.php',
            'icon'  => 'fa-poll', 
            'roles' => ['admin']
        ],
        [
            'title' => 'Survey Builder',
            'url'   => 'survey_builder.php',
            'icon'  => 'fa-wrench',
            'roles' => ['admin']
        ],
        [
            'title' => 'Categories',
            'url'   => 'categories.php',
            'icon'  => 'fa-folder',
            'roles' => ['admin']
        ],
        [
            'title' => 'Users',
            'url'   => 'users.php',
            'icon'  => 'fa-users',
            'roles' => ['admin']
        ],
        [
            'title' => 'Results',
            'url'   => 'results.php',
            'icon'  => 'fa-chart-bar',
            'roles' => ['admin']
        ],
        [
            'title' => 'Settings',
            'url'   => 'settings.php',
            'icon'  => 'fa-cog',
            'roles' => ['admin']
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($siteName) ?> - Admin | <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: <?= htmlspecialchars($themeColor) ?>;
        }
        
        .admin-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-img {
            max-height: 50px;
        }
        
        .admin-nav {
            display: flex;
            gap: 1.5rem;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .logout-btn {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .content-wrapper {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        .admin-nav.vertical {
            flex-direction: column;
            width: 250px;
            background: #f5f5f5;
            padding: 1rem;
            border-right: 1px solid #ddd;
        }
        
        .admin-nav.vertical a {
            color: #333;
            padding: 0.75rem 1rem;
        }
        
        .admin-nav.vertical a:hover,
        .admin-nav.vertical a.active {
            background-color: #e9e9e9;
        }
        
        .admin-nav.vertical .logout-btn {
            margin-top: auto;
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="admin-header">
            <div class="logo">
                <?php if (!empty($siteLogo)): ?>
                    <img src="../assets/images/<?= htmlspecialchars($siteLogo) ?>" 
                         alt="Site Logo" 
                         class="logo-img">
                <?php endif; ?>
                <h1><?= htmlspecialchars($siteName) ?></h1>
            </div>
            <nav class="admin-nav">
                <?php foreach ($adminMenu as $item): ?>
                    <?php
                    $allowedRoles = $item['roles'] ?? ['admin'];
                    if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) continue;
                    ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" 
                       class="<?= basename($_SERVER['PHP_SELF']) === $item['url'] ? 'active' : '' ?>">
                        <i class="fas <?= htmlspecialchars($item['icon']) ?>"></i>
                        <?= htmlspecialchars($item['title']) ?>
                    </a>
                <?php endforeach; ?>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </header>
        <div class="content-wrapper">
            <nav class="admin-nav vertical">
                <?php foreach ($adminMenu as $item): ?>
                    <?php
                    $allowedRoles = $item['roles'] ?? ['admin'];
                    if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) continue;
                    ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" 
                       class="<?= basename($_SERVER['PHP_SELF']) === $item['url'] ? 'active' : '' ?>">
                        <i class="fas <?= htmlspecialchars($item['icon']) ?>"></i>
                        <?= htmlspecialchars($item['title']) ?>
                    </a>
                <?php endforeach; ?>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>