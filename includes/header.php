<?php
require_once '../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School CRM System- <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <h1 class="logo">Survey System</h1>
            <nav class="main-nav">
                <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="chat.php" class="<?= basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Chat
                </a>
                <a href="feedback.php" class="<?= basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : '' ?>">
                    <i class="fas fa-comment-dots"></i> Feedback
                </a>
                <a href="contact.php" class="<?= basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Contact
                </a>
                <a href="inbox.php" class="<?= basename($_SERVER['PHP_SELF']) === 'inbox.php' ? 'active' : '' ?>">
                    <i class="fas fa-inbox"></i> Inbox
                </a>
                
                <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Account
                </a>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>
    <main class="content-wrapper">