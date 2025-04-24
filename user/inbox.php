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

// Fetch online admins (role_name = 'admin' and online = 1)
try {
    $adminStmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role_id, u.last_activity, COALESCE(r.role_name, 'No Role') as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.online = 1 AND (r.role_name = 'admin' OR r.role_name = 'Admin') AND u.id != :self
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
                    <form method="get" class="search-bar" id="onlineUserSearchForm" style="margin-bottom:1.2rem;display:flex;gap:0.2rem;align-items:center;max-width:220px;">
                        <input type="text" name="search" id="onlineUserSearch" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" class="erpnext-input search-mini" style="flex:1;max-width:80px;">
                        <select name="role" id="roleFilter" class="erpnext-input search-mini" style="max-width:70px;">
                            <option value="">All</option>
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
                <div class="telegram-chat-container">
                    <div class="telegram-chat-list" id="chatList">
                        <?php foreach ($messages as $message): ?>
                            <div class="telegram-chat-item message-item <?= $message['sender_id'] == $userId ? 'own' : 'other' ?>" data-status="<?= $message['is_read'] ? 'read' : 'unread' ?>">
                                <div class="chat-header">
                                    <span class="sender"><?= htmlspecialchars($message['sender_name'] ?? '') ?></span>
                                    <span class="date"><?= date('M j, Y g:i a', strtotime($message['sent_at'])) ?></span>
                                </div>
                                <div class="chat-body">
                                    <div class="subject"><?= htmlspecialchars($message['subject'] ?? '') ?></div>
                                    <div class="content"><?= nl2br(htmlspecialchars($message['content'] ?? '')) ?></div>
                                </div>
                                <div class="chat-actions">
                                    <button class="erpnext-btn btn-secondary mark-read" data-id="<?= $message['id'] ?>" <?= $message['is_read'] ? 'disabled' : '' ?>>
                                        <?= $message['is_read'] ? 'Read' : 'Mark as Read' ?>
                                    </button>
                                    <button class="erpnext-btn btn-primary reply-message" data-id="<?= $message['id'] ?>" data-sender-id="<?= $message['sender_id'] ?>" data-sender-name="<?= htmlspecialchars($message['sender_name']) ?>">Reply</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($messages) == 0): ?>
                            <p class="no-messages">No messages found.</p>
                        <?php endif; ?>
                    </div>
                    <form id="telegramSendForm" class="telegram-send-form" style="display:flex;gap:0.5em;margin-top:1em;">
                        <input type="text" id="telegramMessage" class="erpnext-input" placeholder="Type a message..." style="flex:1;">
                        <button type="submit" class="erpnext-btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                    </form>
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

    // Telegram style send message (to admin if any online, else disabled)
    document.getElementById('telegramSendForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const msg = document.getElementById('telegramMessage').value.trim();
        if (!msg) return;
        // Dynamically get receiverId from PHP array (to avoid JS/PHP mismatch)t
        let receiverId = null;
        <?php if (!empty($onlineAdmins)): ?>
        // Output a JS array of online admin IDs0]['id'] ?>;
        $adminIds = [];empty($onlineUsers)): ?>
        foreach ($onlineAdmins as $admin) {ers[0]['id'] ?>;
            $adminIds[] = (int)$admin['id'];
        }   alert('No one is online to receive your message.');
        $userIds = [];
        foreach ($onlineUsers as $user) {
            $userIds[] = (int)$user['id'];
        }ormData.append('receiver_id', receiverId);
        ?>rmData.append('message', msg);
        const onlineAdmins = <?= json_encode($adminIds) ?>;', {
        const onlineUsers = <?= json_encode($userIds) ?>;
        if (onlineAdmins.length > 0) {
            receiverId = onlineAdmins[0];
        } else if (onlineUsers.length > 0) {
            receiverId = onlineUsers[0];egramMessage').value = '';
        } else {tion.reload();
            alert('No one is online to receive your message.');
            return;Failed to send message.');
        }
        const formData = new FormData();
        formData.append('receiver_id', receiverId);
        formData.append('message', msg);
        const response = await fetch('/api/send_message.php', {
            method: 'POST',
            body: formData
        });th: 1200px;
        if (response.ok) {
            document.getElementById('telegramMessage').value = '';
            location.reload();
        } else {s: 8px;
            alert('Failed to send message.');;
        }
    });layout {
}); display: flex;
</script>32px;
<?php include_once __DIR__ . '/includes/footer.php'; ?>
<style>
.inbox-container {
    max-width: 1200px;
    margin: 0 auto;x;
    padding: 20px;px;
    background: #fff;x;
    border-radius: 8px;;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
.inbox-layout {: 0;
    display: flex;tive;
    gap: 32px;;
    align-items: flex-start;
}   flex-direction: column;
.inbox-sidebar {
    width: 290px;
    min-width: 230px;
    max-width: 320px;5rem;
    padding: 18px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
    position: relative;m;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 18px;: 1.5px solid #d1d8dd;
}   margin: 0.5em 0 1.2em 0;
.sidebar-section {
    margin-bottom: 1.5rem;
}   display: flex;
.sidebar-title {on: column;
    font-size: 1.1em;
    font-weight: 600;
    color: #215967;
    margin-bottom: 0.7em;
}   align-items: center;
.sidebar-divider {
    border: none;0;
    border-top: 1.5px solid #d1d8dd;
    margin: 0.5em 0 1.2em 0;
}   width: 10px;
.online-users-list {
    display: flex; 50%;
    flex-direction: column;
    gap: 0.5em;line-block;
}
.online-user {dmin {
    display: flex;07bff;
    align-items: center;
    gap: 0.5em;
    padding: 6px 0;0;
}   color: #215967;
.user-status {
    width: 10px;
    height: 10px;93em;
    border-radius: 50%;
    background: #27ae60;
    display: inline-block;
}   color: #888;
.user-status.admin {m;
    background: #007bff;
}
.username {tions {
    font-weight: 500;
    color: #215967;
}   margin-top: 0.5em;
.user-role {
    font-size: 0.93em;
    color: #888;#27ae60;
}   color: #fff;
.no-users {color: #27ae60;
    color: #888;
    font-size: 0.98em;
    margin: 0.5em 0;150;
}   color: #fff;
.message-actions {
    display: flex;
    gap: 0.5em;ne;
    margin-top: 0.5em;
}   z-index: 9999;
.reply-message {: 0; width: 100vw; height: 100vh;
    background: #27ae60;
    color: #fff;rgba(0,0,0,0.25);
    border-color: #27ae60;
}modal-content {
.reply-message:hover {
    background: #219150;
    color: #fff;x 24px 18px 24px;
}   border-radius: 8px;
.modal {h: 100%;
    display: none;px;
    position: fixed;ve;
    z-index: 9999;2px 16px rgba(0,0,0,0.13);
    left: 0; top: 0; width: 100vw; height: 100vh;
    overflow: auto;
    background: rgba(0,0,0,0.25);
}   right: 18px;
.modal-content {
    background: #fff;
    margin: 7% auto;
    padding: 30px 24px 18px 24px;
    border-radius: 8px;
    width: 100%;h: 1000px) {
    max-width: 420px;
    position: relative; column;
    box-shadow: 0 2px 16px rgba(0,0,0,0.13);
}   }
.close-modal {ebar {
    position: absolute;
    right: 18px;h: 100%;
    top: 12px;-bottom: 20px;
    font-size: 1.5em;
    color: #888;
    cursor: pointer;00px) {
}   .main-content-container {
@media (max-width: 1000px) {2vw;
    .inbox-layout {
        flex-direction: column;
        gap: 0;: 8px;
    }
    .inbox-sidebar {
        width: 100%;;
        max-width: 100%;
        margin-bottom: 20px;
    } input, textarea, select, button {
}   font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
@media (max-width: 700px) {
    .main-content-container {
        padding: 10px 2vw 0 2vw;
    }ackground: #f5f7fa;
    .inbox-container {
        padding: 8px; #d1d8dd;
    }order-radius: 4px;
    .inbox-sidebar {x;
        padding: 8px;
    }ransition: background 0.2s, color 0.2s;
}   cursor: pointer;
body, input, textarea, select, button {
    font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
    font-size: 15px;bfc;
}   color: #fff;
.erpnext-btn {or: #007bfc;
    background: #f5f7fa;
    color: #36414c;imary:hover {
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 18px;
    font-weight: 500;ndary {
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
}   border-color: #d1d8dd;
.erpnext-btn.btn-primary {
    background: #007bfc;ry:hover {
    color: #fff;#e4e8ec;
    border-color: #007bfc;
}erpnext-input {
.erpnext-btn.btn-primary:hover {
    background: #0056b3;
    color: #fff; 12px;
}   font-size: 15px;
.erpnext-btn.btn-secondary {
    background: #f5f7fa;
    color: #36414c;
    border-color: #d1d8dd;
}   outline: none;
.erpnext-btn.btn-secondary:hover {
    background: #e4e8ec;
}
.erpnext-input {
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 15px;;
    background: #f5f7fa;
    color: #36414c;0.7rem;
}   max-width: 220px;
.erpnext-input:focus {
    outline: none;
    border-color: #007bfc;
    background: #fff;
}   border-radius: 4px;
.search-bar {d: #f9fafb;
    background: #f5f7fa;1d8dd;
    border-radius: 5px;
    padding: 2px 2px;
    box-shadow: none;
    border: none;8px;
    margin-bottom: 0.7rem;
    max-width: 220px;x;
}   margin: 0;
.search-mini { 28px;
    font-size: 0.95em;
    padding: 4px 6px;
    border-radius: 4px;;
    background: #f9fafb;ter;
    border: 1px solid #d1d8dd;
    margin: 0;ni i {
}   margin: 0;
.search-btn-mini {
    padding: 5px 8px;00px) {
    font-size: 1em;{
    border-radius: 4px;
    margin: 0;dth: 100%;
    min-width: 28px;m: 20px;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}       max-width: 100%;
.search-btn-mini i {
    margin: 0;ni {
}       max-width: 70px;
@media (max-width: 1000px) {
    .inbox-sidebar {
        width: 100%;00px) {
        max-width: 100%;
        margin-bottom: 20px;mn;
    }   gap: 0.15rem;
    .search-bar {2px 2px;
        flex-wrap: wrap;
        gap: 0.2rem;
        max-width: 100%;
    }   width: 100%;
    .search-mini { 100%;
        max-width: 70px;
    }
}telegram-chat-container {
@media (max-width: 700px) {
    .search-bar {: 8px;
        flex-direction: column;0,0,0,0.07);
        gap: 0.15rem;x 10px 18px;
        padding: 2px 2px;
        max-width: 100%;
    }lex-direction: column;
    .search-mini {
        width: 100%;;
        max-width: 100%;
    }gram-chat-list {
}   flex: 1;
.telegram-chat-container {
    background: #f5f7fa;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.07);
    padding: 18px 18px 10px 18px;
    min-height: 350px;
    display: flex;m {
    flex-direction: column;
    height: 500px;16px;
    max-height: 60vh;px;
}   background: #fff;
.telegram-chat-list { 2px rgba(44,62,80,0.04);
    flex: 1;ottom: 0.2em;
    overflow-y: auto;start;
    margin-bottom: 1em;
    display: flex;
    flex-direction: column;
    gap: 0.7em; #e2efda;
}   align-self: flex-end;
.telegram-chat-item {
    max-width: 80%; .chat-header {
    padding: 12px 16px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(44,62,80,0.04);
    margin-bottom: 0.2em;
    align-self: flex-start;between;
    position: relative;
}telegram-chat-item .chat-body .subject {
.telegram-chat-item.own {
    background: #e2efda;
    align-self: flex-end;
}   margin-bottom: 0.2em;
.telegram-chat-item .chat-header {
    font-size: 0.97em;hat-body .content {
    color: #215967;
    font-weight: 600;
    margin-bottom: 0.2em;
    display: flex;m .chat-actions {
    justify-content: space-between;
}   display: flex;
.telegram-chat-item .chat-body .subject {
    font-size: 1em;
    font-weight: 500;
    color: #007bfc; solid #d1d8dd;
    margin-bottom: 0.2em;
}   background: #f5f7fa;
.telegram-chat-item .chat-body .content {
    font-size: 1em;
    color: #36414c;20px;
}   padding: 10px 16px;
.telegram-chat-item .chat-actions {
    margin-top: 0.5em;#d1d8dd;
    display: flex;ff;
    gap: 0.5em;
}media (max-width: 900px) {
.telegram-send-form {ntainer {
    border-top: 1px solid #d1d8dd;
    padding-top: 0.7em;px;
    background: #f5f7fa;
}   }
#telegramMessage {-item {
    border-radius: 20px;
    padding: 10px 16px;px;
    font-size: 1em;
    border: 1px solid #d1d8dd;
    background: #fff;
}
@media (max-width: 900px) {
    .telegram-chat-container {        padding: 8px 4px 6px 4px;        min-height: 220px;        height: 320px;    }    .telegram-chat-item {        max-width: 96%;        padding: 8px 10px;    }}</style><?php ob_end_flush(); ?>