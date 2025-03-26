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