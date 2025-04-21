<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireLogin();

// Fetch user info for avatar
$user = null;
if (isset($_SESSION['user_id'])) {
    require_once '../includes/config.php';
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $avatar = !empty($user['avatar']) ? $user['avatar'] : 'default.jpg';
} else {
    $avatar = 'default.jpg';
}
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
    <style>
        .header-avatar-container {
            position: absolute;
            top: 18px;
            right: 32px;
            display: flex;
            align-items: center;
            z-index: 10;
        }
        .header-avatar-img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        @media (max-width: 600px) {
            .header-avatar-container {
                top: 10px;
                right: 10px;
            }
            .header-avatar-img {
                width: 36px;
                height: 36px;
            }
        }
        .main-header {
            position: relative;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <h1 class="logo">School CRM System</h1>
            <nav class="main-nav">
                <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="feedback.php" class="<?= basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : '' ?>">
                    <i class="fas fa-comment-dots"></i> Feedback
                </a>
                <a href="contact.php" class="<?= basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : '' ?>">
                    <i class="fas fa-phone"></i> Contact
                </a>
                <a href="messages.php" class="<?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Messages
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
            <div class="header-avatar-container">
                <a href="profile.php" title="My Profile">
                    <img src="../uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="Profile Picture" class="header-avatar-img" onerror="this.src='../uploads/avatars/default.jpg'">
                </a>
            </div>
        </div>
    </header>
    <main class="content-wrapper"></main>