<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once 'includes/auth.php';

if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$pageTitle = "Notifications";

// Fetch notifications from the database
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <nav>
                <div class="logo">School CRM</div>
                <ul class="nav-links">
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="notifications-section">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <p>Here are your recent notifications:</p>
                <ul class="notifications-list">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <li>
                                <strong><?= htmlspecialchars($notification['title']) ?></strong>
                                <p><?= htmlspecialchars($notification['message']) ?></p>
                                <small><?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No notifications found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> School CRM. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
