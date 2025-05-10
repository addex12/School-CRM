<?php
// Always use an absolute path for includes to avoid path issues
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Fetch site logo and name from settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_logo', 'site_name')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteLogo = $settings['site_logo'] ?? 'assets/images/default-logo.png';
    $siteName = $settings['site_name'] ?? 'School CRM';
} catch (Exception $e) {
    $siteLogo = 'assets/images/default-logo.png';
    $siteName = 'School CRM';
}

// Sanitize current file name for safe comparison and output
$currentPage = htmlspecialchars(basename($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey System - <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../includes/activity-tracker.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <div class="header-logo">
                <img src="<?php echo htmlspecialchars($siteLogo); ?>" alt="Site Logo" style="height: 40px;">
                <span><?php echo htmlspecialchars($siteName); ?></span>
            </div>
            <nav class="main-nav">
                <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>

                <a href="feedback.php" class="<?= $currentPage === 'feedback.php' ? 'active' : '' ?>">
                    <i class="fas fa-comment-dots"></i> Feedback
                </a>
                <a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Contact
                </a>
                <a href="inbox.php" class="<?= $currentPage === 'inbox.php' ? 'active' : '' ?>">
                    <i class="fas fa-inbox"></i> Inbox
                </a>
                
                <a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Account
                </a>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>
    <main class="content-wrapper"></main></main>