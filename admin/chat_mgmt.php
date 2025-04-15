<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = "Chat Management";
?>
<div class="admin-dashboard">
    <div class="admin-main">
    <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-header">
            <h1><i class="fas fa-comments"></i> Chat Management</h1>
        </div>
        <div class="admin-content" style="display:flex;gap:24px;">
            <div class="chat-sidebar" style="width:260px;min-width:180px;background:#f8f9fa;border-radius:8px;padding:16px 8px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <h3 style="margin-top:0;">Online Users</h3>
                <ul id="onlineUsers" class="chat-user-list" style="list-style:none;padding:0;margin:0;min-height:200px;"></ul>
                <div id="noUsersMsg" style="color:#888;text-align:center;display:none;">No users online</div>
            </div>
            <div class="chat-main" style="flex:1;display:flex;flex-direction:column;max-width:700px;">
                <div id="chatMessages" style="flex:1 1 auto;min-height:350px;max-height:450px;overflow-y:auto;background:#f5f5f5;border-radius:8px;padding:16px;margin-bottom:12px;"></div>
                <div style="display:flex;gap:8px;">
                    <input id="chatInput" type="text" class="form-control" placeholder="Type your message..." style="flex:1;">
                    <button id="sendBtn" class="btn btn-primary">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.chat-user-list li {padding:8px 10px;cursor:pointer;border-radius:5px;}
.chat-user-list li.active,.chat-user-list li:hover{background:#e9ecef;}
.chat-bubble{margin-bottom:10px;padding:8px 14px;border-radius:16px;max-width:68%;display:inline-block;clear:both;}
.admin-bubble{background:#d1e7dd;color:#155724;float:right;}
.user-bubble{background:#e2e3e5;color:#444;float:left;}
.sender{font-weight:bold;margin-right:6px;}
.time{font-size:0.8em;color:#888;margin-left:8px;}
</style>
<script>
// --- Admin Chat Management ---
const adminId = <?= (int)$_SESSION['user_id'] ?>;
const ws = new WebSocket("ws://localhost:8080");
let selectedUserId = null;

ws.onopen = function() {
    ws.send(JSON.stringify({ type: 'auth', user_id: adminId, role: 'admin' }));
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

function loadOnlineUsers() {
    fetch('online_users.php')
        .then(r => r.json())
        .then(users => {
            const list = document.getElementById('onlineUsers');
            const noUsersMsg = document.getElementById('noUsersMsg');
            list.innerHTML = '';
            if (!users.length) {
                noUsersMsg.style.display = 'block';
                return;
            } else {
                noUsersMsg.style.display = 'none';
            }
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
setInterval(loadOnlineUsers, 10000);
window.onload = function() { loadOnlineUsers(); setupSendBox(); };

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
</script>
<?php include '../includes/footer.php'; ?>