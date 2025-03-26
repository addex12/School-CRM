<?php
require_once '../includes/auth.php';
require_once 'setting.php'; 

$stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
requireAdmin();

$siteName = getSystemSetting('site_name', 'Survey System');
$siteLogo = getSystemSetting('site_logo');
$themeColor = getSystemSetting('theme_color', '#3498db');

// Get admin menu configuration with validation
$menuJson = getSystemSetting('admin_menu');
$adminMenu = json_decode($menuJson, true);

if (!$adminMenu || json_last_error() !== JSON_ERROR_NONE) {
    // Fallback to hardcoded menu
    $adminMenu = [
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fa-home'],
        ['title' => 'Surveys', 'url' => 'surveys.php', 'icon' => 'fa-poll'],
        ['title' => 'Survey Builder', 'url' => 'survey_builder.php', 'icon' => 'fa-wrench'],
        ['title' => 'Categories', 'url' => 'categories.php', 'icon' => 'fa-folder'],
        ['title' => 'Users', 'url' => 'users.php', 'icon' => 'fa-users'],
        ['title' => 'Results', 'url' => 'results.php', 'icon' => 'fa-chart-bar'],
        ['title' => 'Settings', 'url' => 'settings.php', 'icon' => 'fa-cog']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($siteName) ?> - Admin | <?= $pageTitle ?? 'Dashboard' ?></title>    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $themeColor; ?>;
        }
        .admin-header {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="admin-header">
            <div class="logo">
                <?php if ($siteLogo): ?>
                    <img src="../assets/images/<?php echo htmlspecialchars($siteLogo); ?>" alt="Site Logo" class="logo-img">
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($siteName); ?></h1>
            </div>
            <nav class="admin-nav">
                <?php foreach ($adminMenu as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) == $item['url'] ? 'active' : ''; ?>">
                        <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <?php echo htmlspecialchars($item['title']); ?>
                    </a>
                <?php endforeach; ?>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </header>
        <div class="content-wrapper">
            <nav class="admin-nav">
    <?php foreach ($adminMenu as $item):
        $allowedRoles = $item['roles'] ?? ['admin'];
        if (!in_array($_SESSION['role'], $allowedRoles)) continue;
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