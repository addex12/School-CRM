<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/setting.php';

// Update user activity
$stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
requireAdmin();

// Get site settings
$siteName     = getSystemSetting('site_name', '');
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