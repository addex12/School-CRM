<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$userId = $_SESSION['user_id'];

// Fetch messages for the inbox
$stmt = $pdo->prepare("
    SELECT m.id, m.subject, m.content, m.sender_id, m.receiver_id, m.sent_at, m.is_read, u.username AS sender_name 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.sent_at DESC
");
$stmt->execute([$userId]);
$messages = $stmt->fetchAll();

// Fetch online users (active in last 5 minutes)
$onlineThreshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
$onlineUsers = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, role_id, last_activity 
        FROM users 
        WHERE last_activity > ? AND id != ?
        ORDER BY username
    ");
    $stmt->execute([$onlineThreshold, $userId]);
    $onlineUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching online users: " . $e->getMessage());
}

include __DIR__ . 'includes/header.php';
?>

<div class="inbox-container">
    <div class="inbox-layout">
        <div class="inbox-sidebar">
            <h3>Online Users</h3>
            <div class="online-users-list">
                <?php if (count($onlineUsers) > 0): ?>
                    <?php foreach ($onlineUsers as $user): ?>
                        <div class="online-user">
                            <span class="user-status"></span>
                            <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                            <?php if ($user['role_id']): ?>
                                <span class="user-role">(<?= getUserRoleName($user['role_id']) ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-users">No users currently online</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="inbox-main">
            <h1>Your Inbox</h1>

            <div class="inbox-controls">
                <input type="text" id="search" placeholder="Search messages..." class="search-bar">
                <select id="filter" class="filter-dropdown">
                    <option value="all">All Messages</option>
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                </select>
            </div>

            <div class="message-list">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item" data-status="<?= $message['is_read'] ? 'read' : 'unread' ?>">
                            <div class="message-header">
                                <span class="sender"><?= htmlspecialchars($message['sender_name']) ?></span>
                                <span class="date"><?= date('M j, Y g:i a', strtotime($message['sent_at'])) ?></span>
                            </div>
                            <div class="message-body">
                                <h3 class="subject"><?= htmlspecialchars($message['subject']) ?></h3>
                                <p class="content"><?= htmlspecialchars(substr($message['content'], 0, 100)) ?>...</p>
                            </div>
                            <div class="message-actions">
                                <button class="btn btn-primary view-message" data-id="<?= $message['id'] ?>">View</button>
                                <button class="btn btn-secondary mark-read" data-id="<?= $message['id'] ?>" <?= $message['is_read'] ? 'disabled' : '' ?>>
                                    <?= $message['is_read'] ? 'Read' : 'Mark as Read' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-messages">No messages found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    const filterDropdown = document.getElementById('filter');
    const messages = document.querySelectorAll('.message-item');

    // Search functionality
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        messages.forEach(message => {
            const subject = message.querySelector('.subject').textContent.toLowerCase();
            const content = message.querySelector('.content').textContent.toLowerCase();
            const sender = message.querySelector('.sender').textContent.toLowerCase();
            if (subject.includes(query) || content.includes(query) || sender.includes(query)) {
                message.style.display = '';
            } else {
                message.style.display = 'none';
            }
        });
    });

    // Filter functionality
    filterDropdown.addEventListener('change', () => {
        const filter = filterDropdown.value;
        messages.forEach(message => {
            if (filter === 'all') {
                message.style.display = '';
            } else if (filter === 'unread' && message.dataset.status === 'unread') {
                message.style.display = '';
            } else if (filter === 'read' && message.dataset.status === 'read') {
                message.style.display = '';
            } else {
                message.style.display = 'none';
            }
        });
    });

    // Mark as read functionality
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', async () => {
            const messageId = button.dataset.id;
            const messageItem = button.closest('.message-item');
            
            try {
                const response = await fetch(`/api/mark-read.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ messageId })
                });
                
                if (response.ok) {
                    messageItem.dataset.status = 'read';
                    button.textContent = 'Read';
                    button.disabled = true;
                } else {
                    console.error('Failed to mark message as read');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

    // View message functionality
    document.querySelectorAll('.view-message').forEach(button => {
        button.addEventListener('click', () => {
            const messageId = button.dataset.id;
            window.location.href = `/view-message.php?id=${messageId}`;
        });
    });
});
</script>

<style>
.inbox-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.inbox-layout {
    display: flex;
    gap: 20px;
}

.inbox-sidebar {
    width: 250px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.inbox-main {
    flex: 1;
}

.online-users-list {
    margin-top: 15px;
}

.online-user {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    margin-bottom: 5px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.user-status {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #28a745;
    margin-right: 8px;
}

.username {
    font-weight: 500;
    color: #333;
}

.user-role {
    font-size: 0.8em;
    color: #6c757d;
    margin-left: 5px;
}

.no-users {
    color: #6c757d;
    font-size: 0.9em;
    text-align: center;
    padding: 10px;
}

.inbox-controls {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.search-bar {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-right: 10px;
}

.filter-dropdown {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.message-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.message-item {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
    transition: background 0.3s;
}

.message-item:hover {
    background: #f1f1f1;
}

.message-item[data-status="unread"] {
    background: #e7f3ff;
    border-left: 3px solid #007bff;
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.sender {
    font-weight: bold;
    color: #333;
}

.date {
    font-size: 0.9em;
    color: #666;
}

.subject {
    font-size: 1.1em;
    margin: 0;
    color: #007bff;
}

.content {
    font-size: 0.9em;
    color: #555;
}

.message-actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
    transition: opacity 0.3s;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: #007bff;
    color: #fff;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.no-messages {
    text-align: center;
    color: #666;
    font-size: 1.1em;
    padding: 20px;
}

@media (max-width: 768px) {
    .inbox-layout {
        flex-direction: column;
    }
    
    .inbox-sidebar {
        width: 100%;
    }
}
</style>

<?php include __DIR__ . 'includes/footer.php'; ?>