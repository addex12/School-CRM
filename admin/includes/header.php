<?php
require_once '../../includes/auth.php';
require_once '../includes/config.php'; // Include config to initialize $pdo
$auth->requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo SITE_NAME; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Panel</h1>
            <nav>
                <a href="dashboard.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="surveys.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'surveys.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-poll-h"></i> Surveys
                </a>
                <a href="survey_builder.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'survey_builder.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tools"></i> Builder
                </a>
                <a href="categories.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'categories.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-folder"></i> Categories
                </a>
                <a href="users.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="results.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'results.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-chart-bar"></i> Results
                </a>
                <a href="feedback.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'feedback.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-comment"></i> Feedback
                </a>
                <a href="contact_messages.php" <?php echo basename(path: $_SERVER['PHP_SELF']) == 'contact_messages.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="../../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </header>
        <div class="content">