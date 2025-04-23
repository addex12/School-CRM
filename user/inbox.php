<?php
ob_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Update last active
updateLastActive($userId);

// Sorting/filtering logic for online users
$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');
$roleSql = $roleFilter ? "AND r.role_name = :role" : "";
$searchSql = $search ? "AND (u.username LIKE :search OR u.email LIKE :search)" : "";

// Fetch online users (active in last 5 minutes)
$onlineThreshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
$onlineUsers = [];
$roles = [];
try {
    $sql = "
        SELECT u.id, u.username, u.email, u.role_id, u.last_activity, COALESCE(r.role_name, 'No Role') as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.last_activity > :threshold AND u.id != :self
        $roleSql
        $searchSql
        ORDER BY u.last_activity DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':threshold', $onlineThreshold);
    $stmt->bindValue(':self', $userId);
    if ($roleFilter) $stmt->bindValue(':role', $roleFilter);
    if ($search) $stmt->bindValue(':search', '%' . $search . '%');
    $stmt->execute();
    $onlineUsers = $stmt->fetchAll();

    // Fetch all roles for filter dropdown
    $roles = $pdo->query("SELECT DISTINCT role_name FROM roles WHERE role_name IS NOT NULL ORDER BY role_name")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching online users: " . $e->getMessage());
}

// Fetch messages
try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.subject, m.content, m.sender_id, m.receiver_id, m.sent_at, m.is_read, u.username AS sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ?
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute([$userId]);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    $messages = [];
}

function getUserRoleName($roleId) {
    global $pdo;
    static $roles = [];
    if (empty($roles)) {
        $stmt = $pdo->query("SELECT id, role_name FROM roles");
        $roles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $roles[$roleId] ?? 'Unknown';
}
?>
<?php include_once __DIR__ . '/includes/header.php'; ?>
<div class="main-content-container" style="max-width:1200px;margin:0 auto;padding:40px 20px 0 20px;">
    <div class="inbox-container">
        <div class="inbox-layout">
            <div class="inbox-sidebar">
                <h3>Online Users</h3>
                <form method="get" class="search-bar" id="onlineUserSearchForm" style="margin-bottom:1.2rem;display:flex;gap:0.5rem;">
                    <input type="text" name="search" id="onlineUserSearch" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
                    <select name="role" id="roleFilter">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>" <?= $role === $roleFilter ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="erpnext-btn btn-primary"><i class="fas fa-search"></i></button>
                    <a href="inbox.php" class="erpnext-btn btn-secondary">Clear</a>
                </form>
                <div class="online-users-list">
                    <?php if (count($onlineUsers) > 0): ?>
                        <?php foreach ($onlineUsers as $user): ?>
                            <div class="online-user">
                                <span class="user-status"></span>
                                <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                                <span class="user-role">(<?= htmlspecialchars($user['role_name'] ?? getUserRoleName($user['role_id'])) ?>)</span>
                                <button class="btn btn-chat" data-user-id="<?= $user['id'] ?>">Chat</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-users">No users currently online</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="inbox-main">
                <h1>Your Inbox</h1>
                <div class="inbox-controls" style="display:flex;gap:1rem;margin-bottom:20px;">
                    <input type="text" id="search" placeholder="Search messages..." class="search-bar" style="flex:1;">
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
                                    <span class="sender"><?= htmlspecialchars($message['sender_name'] ?? '') ?></span>
                                    <span class="date"><?= date('M j, Y g:i a', strtotime($message['sent_at'])) ?></span>
                                </div>
                                <div class="message-body">
                                    <h3 class="subject"><?= htmlspecialchars($message['subject'] ?? '') ?></h3>
                                    <p class="content"><?= htmlspecialchars(substr($message['content'] ?? '', 0, 100)) ?>...</p>
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
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Search/filter for messages
    const searchInput = document.getElementById('search');
    const filterDropdown = document.getElementById('filter');
    const messages = document.querySelectorAll('.message-item');
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
                const response = await fetch('/api/mark-read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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
            window.location.href = 'view_message.php?id=' + messageId;
        });
    });

    // Chat button redirects to messages.php with selected user id
    document.querySelectorAll('.btn-chat').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.userId;
            window.location.href = '../admin/messages.php?user_id=' + encodeURIComponent(userId);
        });
    });
});
</script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
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

.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    /* Remove default browser styling for disabled buttons (like stop icon) */
    background-image: none !important;
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

/* Chat Modal Styles */
.chat-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.chat-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
}

.close-chat {
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
    font-size: 20px;
    color: #333;
}

.chat-messages {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    padding: 10px;
    background: #f9f9f9;
}

#chatInput {
    width: calc(100% - 80px);
    padding: 10px;
    margin-right: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#chatForm button {
    padding: 10px 20px;
}
</style>

<?php ob_end_flush(); ?>
