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
        WHERE u.last_activity > :threshold AND u.id != :self AND u.role_id != 1
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

// Fetch online admins (role_id = 1 and active in last 5 min)
try {
    $adminStmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role_id, u.last_activity, COALESCE(r.role_name, 'No Role') as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.role_id = 1 AND u.last_activity > :threshold AND u.id != :self
        ORDER BY u.username ASC
    ");
    $adminStmt->bindValue(':threshold', $onlineThreshold);
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
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Online Admins</h3>
                    <div class="online-users-list">
                        <?php if (count($onlineAdmins) > 0): ?>
                            <?php foreach ($onlineAdmins as $user): ?>
                                <div class="online-user">
                                    <span class="user-status admin"></span>
                                    <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                                    <span class="user-role">(<?= htmlspecialchars($user['role_name']) ?>)</span>
                                    <button class="erpnext-btn btn-primary btn-chat" data-user-id="<?= $user['id'] ?>">Chat</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-users">No admins currently online</p>
                        <?php endif; ?>
                    </div>
                </div>
                <hr class="sidebar-divider">
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Online Users</h3>
                    <form method="get" class="search-bar" id="onlineUserSearchForm" style="margin-bottom:1.2rem;display:flex;gap:0.3rem;align-items:center;">
                        <input type="text" name="search" id="onlineUserSearch" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" class="erpnext-input search-mini" style="flex:1;max-width:110px;">
                        <select name="role" id="roleFilter" class="erpnext-input search-mini" style="max-width:90px;">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role) ?>" <?= $role === $roleFilter ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="erpnext-btn btn-primary search-btn-mini" title="Search"><i class="fas fa-search"></i></button>
                        <a href="inbox.php" class="erpnext-btn btn-secondary search-btn-mini" title="Clear"><i class="fas fa-times"></i></a>
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
                                    <button class="erpnext-btn btn-primary reply-message" data-id="<?= $message['id'] ?>" data-sender-id="<?= $message['sender_id'] ?>" data-sender-name="<?= htmlspecialchars($message['sender_name']) ?>">Reply</button>
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

<!-- Reply Modal -->
<div id="replyModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-modal" id="closeReplyModal">&times;</span>
        <h2>Reply to <span id="replyToName"></span></h2>
        <form id="replyForm">
            <input type="hidden" id="replyReceiverId" name="receiver_id">
            <textarea id="replyMessage" name="message" rows="4" class="erpnext-input" style="width:100%;" placeholder="Type your reply..." required></textarea>
            <button type="submit" class="erpnext-btn btn-primary" style="margin-top:10px;">Send Reply</button>
        </form>
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

    // Reply modal logic
    const replyModal = document.getElementById('replyModal');
    const closeReplyModal = document.getElementById('closeReplyModal');
    const replyForm = document.getElementById('replyForm');
    const replyToName = document.getElementById('replyToName');
    const replyReceiverId = document.getElementById('replyReceiverId');
    const replyMessage = document.getElementById('replyMessage');

    document.querySelectorAll('.reply-message').forEach(btn => {
        btn.addEventListener('click', () => {
            replyToName.textContent = btn.getAttribute('data-sender-name');
            replyReceiverId.value = btn.getAttribute('data-sender-id');
            replyMessage.value = '';
            replyModal.style.display = 'block';
        });
    });
    closeReplyModal.onclick = () => replyModal.style.display = 'none';
    window.onclick = (event) => { if (event.target == replyModal) replyModal.style.display = 'none'; };

    replyForm.onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(replyForm);
        const response = await fetch('/api/send_message.php', {
            method: 'POST',
            body: formData
        });
        if (response.ok) {
            replyModal.style.display = 'none';
            alert('Reply sent!');
            location.reload();
        } else {
            alert('Failed to send reply.');
        }
    };
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
    gap: 32px;
    align-items: flex-start;
}
.inbox-sidebar {
    width: 290px;
    min-width: 230px;
    max-width: 320px;
    padding: 18px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.sidebar-section {
    margin-bottom: 1.5rem;
}
.sidebar-title {
    font-size: 1.1em;
    font-weight: 600;
    color: #215967;
    margin-bottom: 0.7em;
}
.sidebar-divider {
    border: none;
    border-top: 1.5px solid #d1d8dd;
    margin: 0.5em 0 1.2em 0;
}
.online-users-list {
    display: flex;
    flex-direction: column;
    gap: 0.5em;
}
.online-user {
    display: flex;
    align-items: center;
    gap: 0.5em;
    padding: 6px 0;
}
.user-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #27ae60;
    display: inline-block;
}
.user-status.admin {
    background: #007bff;
}
.username {
    font-weight: 500;
    color: #215967;
}
.user-role {
    font-size: 0.93em;
    color: #888;
}
.no-users {
    color: #888;
    font-size: 0.98em;
    margin: 0.5em 0;
}
.message-actions {
    display: flex;
    gap: 0.5em;
    margin-top: 0.5em;
}
.reply-message {
    background: #27ae60;
    color: #fff;
    border-color: #27ae60;
}
.reply-message:hover {
    background: #219150;
    color: #fff;
}
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0; width: 100vw; height: 100vh;
    overflow: auto;
    background: rgba(0,0,0,0.25);
}
.modal-content {
    background: #fff;
    margin: 7% auto;
    padding: 30px 24px 18px 24px;
    border-radius: 8px;
    width: 100%;
    max-width: 420px;
    position: relative;
    box-shadow: 0 2px 16px rgba(0,0,0,0.13);
}
.close-modal {
    position: absolute;
    right: 18px;
    top: 12px;
    font-size: 1.5em;
    color: #888;
    cursor: pointer;
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
.search-bar {
    background: #f5f7fa;
    border-radius: 5px;
    padding: 3px 4px;
    box-shadow: none;
    border: none;
    margin-bottom: 0.7rem;
}
.search-mini {
    font-size: 0.97em;
    padding: 5px 8px;
    border-radius: 4px;
    background: #f9fafb;
    border: 1px solid #d1d8dd;
    margin: 0;
}
.search-btn-mini {
    padding: 6px 10px;
    font-size: 1em;
    border-radius: 4px;
    margin: 0;
    min-width: 32px;
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.search-btn-mini i {
    margin: 0;
}
@media (max-width: 1000px) {
    .inbox-sidebar {
        width: 100%;
        max-width: 100%;
        margin-bottom: 20px;
    }
    .search-bar {
        flex-wrap: wrap;
        gap: 0.3rem;
    }
    .search-mini {
        max-width: 100px;
    }
}
@media (max-width: 700px) {
    .search-bar {
        flex-direction: column;
        gap: 0.2rem;
        padding: 2px 2px;
    }
    .search-mini {
        width: 100%;
        max-width: 100%;
    }
}
</style>

<?php ob_end_flush(); ?>
