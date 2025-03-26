<?php
require_once '../includes/auth.php';
requireAdmin();

// Fetch chat messages with user info
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.email 
    FROM chat_messages c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$messages = $stmt->fetchAll();

$pageTitle = "Chat Management";
include '../includes/header.php';
?>

<div class="admin-content">
    <h2><i class="fas fa-comments"></i> Chat Management</h2>
    
    <div class="content-section">
        <div class="search-filter">
            <input type="text" placeholder="Search messages..." class="search-input">
            <select class="status-filter">
                <option value="all">All Statuses</option>
                <option value="open">Open</option>
                <option value="pending">Pending</option>
                <option value="resolved">Resolved</option>
            </select>
        </div>
        
        <div class="chat-list">
            <?php foreach($messages as $message): ?>
            <div class="chat-item" data-status="<?= $message['status'] ?>">
                <div class="chat-header">
                    <span class="user-info">
                        <?= htmlspecialchars($message['username']) ?> 
                        <small><?= htmlspecialchars($message['email']) ?></small>
                    </span>
                    <span class="chat-meta">
                        <?= date('M j, Y g:i a', strtotime($message['created_at'])) ?>
                        <span class="status-badge <?= $message['status'] ?>">
                            <?= ucfirst($message['status']) ?>
                        </span>
                    </span>
                </div>
                <div class="chat-body">
                    <?= htmlspecialchars($message['message']) ?>
                    <div class="chat-actions">
                        <select class="status-change" data-message-id="<?= $message['id'] ?>">
                            <option value="open" <?= $message['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="pending" <?= $message['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="resolved" <?= $message['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                        <button class="btn reply-btn" data-email="<?= htmlspecialchars($message['email']) ?>">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>