<?php
// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Define the time_ago function
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return $diff . " seconds ago";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " minutes ago";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " hours ago";
    } else {
        return floor($diff / 86400) . " days ago";
    }
}
?>

<style>
    .notifications-dropdown .badge {
        font-size: 0.8rem; /* Reduced font size */
        padding: 4px 8px; /* Smaller padding */
        background: #e74c3c; /* Red background */
        color: #fff;
        border-radius: 50%;
        font-weight: 600;
    }
    .notifications-list .notification-item {
        font-size: 0.85rem; /* Reduced font size */
        padding: 6px 10px; /* Smaller padding */
        border-bottom: 1px solid #ddd;
    }
    .notifications-list .notification-item small {
        font-size: 0.75rem; /* Smaller timestamp */
        color: #888;
    }
</style>

<div class="notifications-dropdown">
    <i class="fas fa-bell"></i>
    <?php if (count($notifications) > 0): ?>
        <span class="badge"><?= count($notifications) ?></span>
    <?php endif; ?>
    
    <div class="notifications-list">
        <?php foreach ($notifications as $notification): ?>
            <div class="notification-item">
                <?= htmlspecialchars($notification['message']) ?>
                <small><?= time_ago($notification['created_at']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>