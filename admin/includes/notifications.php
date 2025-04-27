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
    .notifications-dropdown {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .notifications-dropdown .badge {
        font-size: 0.8rem;
        padding: 4px 8px;
        background: #e74c3c;
        color: #fff;
        border-radius: 50%;
        font-weight: 600;
        position: absolute;
        top: -5px;
        right: -5px;
    }

    .notifications-list {
        display: none;
        position: absolute;
        top: 30px;
        right: 0;
        background: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        width: 300px;
        z-index: 1000;
    }

    .notifications-dropdown:hover .notifications-list {
        display: block;
    }

    .notifications-list .notification-item {
        font-size: 0.85rem;
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }

    .notifications-list .notification-item:last-child {
        border-bottom: none;
    }

    .notifications-list .notification-item small {
        font-size: 0.75rem;
        color: #888;
        display: block;
        margin-top: 5px;
    }
</style>

<div class="notifications-dropdown">
    <i class="fas fa-bell"></i>
    <?php if (count($notifications) > 0): ?>
        <span class="badge"><?= count($notifications) ?></span>
    <?php endif; ?>
    <div class="notifications-list">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item">
                    <?= htmlspecialchars($notification['message']) ?>
                    <small><?= time_ago($notification['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="notification-item">No new notifications</div>
        <?php endif; ?>
    </div>
</div>