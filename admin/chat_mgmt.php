<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
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
<script>
// --- Admin Chat Management ---
const adminId = <?= (int)$_SESSION['user_id'] ?>;
const ws = new WebSocket("ws://localhost:8080");
let selectedUserId = null;

ws.onopen = function() {
    // Authenticate as admin
    ws.send(JSON.stringify({ type: 'auth', user_id: adminId, role: 'admin' }));
    // Start ping interval
    setInterval(() => ws.send(JSON.stringify({type: 'ping'})), 20000);
};

ws.onmessage = function(event) {
    let data = JSON.parse(event.data);
    if (data.type === 'chat') {
        if (selectedUserId && (data.from == selectedUserId || data.to == selectedUserId)) {
            appendChatMessage(data, data.from == adminId ? 'admin' : 'user');
        }
    }
};

function sendMessage(msg) {
    if (!selectedUserId || !msg.trim()) return;
    ws.send(JSON.stringify({
        type: "chat",
        message: msg,
        to: selectedUserId
    }));
}

// UI: Load online users
function loadOnlineUsers() {
    fetch('online_users.php')
        .then(r => r.json())
        .then(users => {
            const list = document.getElementById('onlineUsers');
            list.innerHTML = '';
            users.forEach(u => {
                const li = document.createElement('li');
                li.textContent = u.username + (u.fullname ? ' ('+u.fullname+')' : '');
                li.className = 'user-list-item';
                li.onclick = () => selectUser(u.id);
                if (selectedUserId == u.id) li.classList.add('active');
                list.appendChild(li);
            });
        });
}
setInterval(loadOnlineUsers, 10000); // refresh every 10s
window.onload = loadOnlineUsers;

function selectUser(userId) {
    selectedUserId = userId;
    document.getElementById('chatMessages').innerHTML = '';
    fetch('chat_history.php?user_id=' + userId)
        .then(r => r.json())
        .then(msgs => {
            msgs.forEach(m => appendChatMessage(m, m.from == adminId ? 'admin' : 'user'));
        });
}

function appendChatMessage(msg, sender) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'chat-bubble ' + (sender === 'admin' ? 'admin-bubble' : 'user-bubble');
    div.innerHTML = `<span class='sender'>${sender === 'admin' ? 'You' : 'User'}</span>: ` +
        `<span class='msg'>${msg.message}</span> <span class='time'>${msg.created_at ? msg.created_at : ''}</span>`;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

// Send on button click or Enter
function setupSendBox() {
    const input = document.getElementById('chatInput');
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            sendMessage(input.value);
            input.value = '';
        }
    });
    document.getElementById('sendBtn').onclick = function() {
        sendMessage(input.value);
        input.value = '';
    };
}
window.onload = function() { loadOnlineUsers(); setupSendBox(); };
</script>
<?php include '../includes/footer.php'; ?>