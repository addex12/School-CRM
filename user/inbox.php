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

// Fetch online admins (role_id = 0 and online = 1)
try {
    $adminStmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role_id, u.last_activity, COALESCE(r.role_name, 'No Role') as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.role_id = 0 AND u.online = 1 AND u.id != :self
        ORDER BY u.username ASC
    ");
    $adminStmt->bindValue(':self', $userId);
    $adminStmt->execute();
    $onlineAdmins = $adminStmt->fetchAll();
} catch (PDOException $e) {
    $onlineAdmins = [];
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
            <aside class="inbox-sidebar">
                <h3 style="margin-bottom: 1rem;">Online Admins</h3>
                <div class="online-users-list">
                    <?php if (count($onlineAdmins) > 0): ?>
                        <?php foreach ($onlineAdmins as $user): ?>
                            <div class="online-user">
                                <span class="user-status"></span>
                                <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                                <span class="user-role">(<?= htmlspecialchars($user['role_name']) ?>)</span>
                                <button class="erpnext-btn btn-primary btn-chat" data-user-id="<?= $user['id'] ?>">Chat</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-users">No admins currently online</p>
                    <?php endif; ?>
                </div>
                <hr style="margin: 1.5rem 0;">
                <h3 style="margin-bottom: 1rem;">Online Users</h3>
                <form method="get" class="search-bar" id="onlineUserSearchForm" style="margin-bottom:1.2rem;display:flex;gap:0.5rem;">
                    <input type="text" name="search" id="onlineUserSearch" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" class="erpnext-input" style="flex:1;">
                    <select name="role" id="roleFilter" class="erpnext-input">
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
                                <button class="erpnext-btn btn-primary btn-chat" data-user-id="<?= $user['id'] ?>">Chat</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-users">No users currently online</p>
                    <?php endif; ?>
                </div>
            </aside>
            <main class="inbox-main">
                <h1>Your Inbox</h1>
                <div class="inbox-controls" style="display:flex;gap:1rem;margin-bottom:20px;">
                    <input type="text" id="search" placeholder="Search messages..." class="erpnext-input search-bar" style="flex:1;">
                    <select id="filter" class="erpnext-input filter-dropdown">
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
                                    <button class="erpnext-btn btn-primary view-message" data-id="<?= $message['id'] ?>">View</button>
                                    <button class="erpnext-btn btn-secondary mark-read" data-id="<?= $message['id'] ?>" <?= $message['is_read'] ? 'disabled' : '' ?>>
                                        <?= $message['is_read'] ? 'Read' : 'Mark as Read' ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-messages">No messages found.</p>
                    <?php endif; ?>
                </div>
            </main>
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
    align-items: flex-start;
}
.inbox-sidebar {
    width: 270px;
    min-width: 230px;
    max-width: 320px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.inbox-main {
    flex: 1;
    min-width: 0;
}
@media (max-width: 1000px) {
    .inbox-layout {
        flex-direction: column;
        gap: 0;
    }
    .inbox-sidebar {
        width: 100%;
        max-width: 100%;
        margin-bottom: 20px;
    }
}
@media (max-width: 700px) {
    .main-content-container {
        padding: 10px 2vw 0 2vw;
    }
    .inbox-container {
        padding: 8px;
    }
    .inbox-sidebar {
        padding: 8px;
    }
}
body, input, textarea, select, button {
    font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
    font-size: 15px;
}
.erpnext-btn {
    background: #f5f7fa;
    color: #36414c;
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 18px;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
}
.erpnext-btn.btn-primary {
    background: #007bfc;
    color: #fff;
    border-color: #007bfc;
}
.erpnext-btn.btn-primary:hover {
    background: #0056b3;
    color: #fff;
}
.erpnext-btn.btn-secondary {
    background: #f5f7fa;
    color: #36414c;
    border-color: #d1d8dd;
}
.erpnext-btn.btn-secondary:hover {
    background: #e4e8ec;
}
.erpnext-input {
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 15px;
    background: #f5f7fa;
    color: #36414c;
}
.erpnext-input:focus {
    outline: none;
    border-color: #007bfc;
    background: #fff;
}
</style>

<?php ob_end_flush(); ?>
