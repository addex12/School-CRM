<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once 'includes/auth.php';

if (!class_exists('Auth')) {
    class Auth {
        public static function isLoggedIn(): bool {
            return isset($_SESSION['user_id']);
        }
    }
}

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
    <style>
        body {
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .notifications-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .notifications-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .notifications-list li {
            padding: 15px;
            border-bottom: 1px solid #f0f2f5;
        }
        .notifications-list li:last-child {
            border-bottom: none;
        }
        .notifications-list strong {
            font-size: 1rem;
            color: #2c3e50;
        }
        .notifications-list p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        .notifications-list small {
            color: #95a5a6;
            font-size: 0.85rem;
        }
        .empty-state {
            text-align: center;
            color: #95a5a6;
            font-size: 1rem;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="notifications-header">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
        </header>
        <main>
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
                    <div class="empty-state">No notifications found.</div>
                <?php endif; ?>
            </ul>
        </main>
    </div>
</body>
</html>
