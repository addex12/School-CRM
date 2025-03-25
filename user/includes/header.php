<?php
require_once __DIR__ . '/../../includes/auth.php'; // Use __DIR__ for an absolute path

$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Panel</title>
    <link rel="stylesheet" href="/survey/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            <nav>
                <a href="/survey/user/dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="/survey/user/survey.php" <?php echo basename($_SERVER['PHP_SELF']) == 'survey.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-poll-h"></i> Surveys
                </a>
                <a href="/survey/user/chat.php" <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-comments"></i> Chat
                </a>
                <a href="/survey/user/feedback.php" <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-comment-dots"></i> Feedback
                </a>
                <a href="/survey/user/contact.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-envelope"></i> Contact
                </a>
                <a href="/survey/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </header>
        <div class="content"></div>
    </div>
</body>
</html>