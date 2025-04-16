<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();
$pageTitle = "Chat Management";
?>
<?php include 'includes/admin_sidebar.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<div class="container-fluid admin-chat-page" style="margin-left:240px;min-height:100vh;padding:0;">
    <div class="row flex-nowrap" style="min-height:100vh;">
        <div class="col-12 col-lg-3 px-0 border-end bg-light chat-sidebar" style="min-width:220px;max-width:340px;">
            <div class="py-4 px-3">
                <h5 class="mb-4"><i class="fas fa-users text-primary me-2"></i>Online Users</h5>
                <ul id="onlineUsers" class="list-unstyled chat-user-list mb-0"></ul>
                <div id="noUsersMsg" class="text-muted text-center mt-3" style="display:none;">No users online</div>
            </div>
        </div>
        <div class="col px-0 d-flex flex-column" style="background:#f7f8fa;min-height:100vh;">
            <div class="admin-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h2 class="h4 mb-0"><i class="fas fa-comments text-primary me-2"></i> Chat Management</h2>
            </div>
            <div class="flex-grow-1 d-flex flex-column p-3 p-md-4" style="max-width:700px;margin:auto;width:100%;">
                <div id="chatMessages" class="flex-grow-1 overflow-auto mb-3 rounded-3 p-3 bg-white shadow-sm" style="min-height:350px;max-height:480px;"></div>
                <div class="d-flex gap-2">
                    <input id="chatInput" type="text" class="form-control" placeholder="Type your message...">
                    <button id="sendBtn" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

</div>
<style>
.admin-chat-page .chat-sidebar {
    border-right: 1px solid #e3e3e3;
    background: #f8f9fa;
    min-height: 100vh;
}
.chat-user-list li {
    padding: 10px 12px;
    cursor: pointer;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: background 0.15s;
    font-size: 1.02rem;
}
.chat-user-list li.active, .chat-user-list li:hover {
    background: #e3e9f2;
    color: #0d6efd;
    font-weight: 500;
}
.chat-user-avatar {
    width: 32px; height: 32px; border-radius: 50%; background: #dbeafe; color: #2563eb;
    display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.1em;
    border: 2px solid #b6d4fe; margin-right: 8px;
}
.online-dot {
    width: 9px; height: 9px; background: #38d39f; border-radius: 50%; margin-right: 8px; display: inline-block;
}
#chatMessages {
    background: #fff;
    min-height: 340px;
    max-height: 480px;
    overflow-y: auto;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(44,62,80,0.07);
}
.chat-bubble {
    margin-bottom: 12px;
    padding: 12px 18px;
    border-radius: 18px;
    max-width: 75%;
    display: inline-block;
    clear: both;
    font-size: 1.04em;
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.admin-bubble {
    background: #d1e7dd;
    color: #155724;
    float: right;
    text-align: right;
}
.user-bubble {
    background: #e2e3e5;
    color: #444;
    float: left;
}
.sender {
    font-weight: 700;
    margin-right: 8px;
}
.time {
    font-size: 0.88em;
    color: #888;
    margin-left: 8px;
}
@media (max-width: 991px) {
    .admin-chat-page { margin-left:0 !important; }
    .chat-sidebar { min-width: 100px !important; max-width: 100vw !important; }
}
@media (max-width: 700px) {
    .admin-chat-page .row.flex-nowrap { flex-direction: column !important; }
    .chat-sidebar { border-right: none; border-bottom: 1px solid #e3e3e3; }
}
</style>

        </div>
    </div>
</div>

<script>
// --- Admin Chat Management ---
const adminId = <?= (int)$_SESSION['user_id'] ?>;
const ws = new WebSocket("ws://localhost:8080");
let selectedUserId = null;
let selectedUserName = null;

ws.onopen = function() {
    ws.send(JSON.stringify({ type: 'auth', user_id: adminId, role: 'admin' }));
    setInterval(() => ws.send(JSON.stringify({type: 'ping'})), 20000);
};

ws.onmessage = function(event) {
    let data = JSON.parse(event.data);
    if (data.type === 'chat') {
        if (selectedUserId && (data.from == selectedUserId || data.to == selectedUserId)) {
            appendChatMessage(data, data.from == adminId ? 'admin' : 'user', data.username);
        }
    }
};

function sendMessage(msg) {
    if (!selectedUserId || !msg.trim()) {
        alert('Please select a user and type a message.');
        return;
    }
    if (ws.readyState !== WebSocket.OPEN) {
        alert('WebSocket connection is not open. Please try again later.');
        return;
    }
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
                li.className = 'user-list-item d-flex align-items-center';
                let initials = u.username.split(' ').map(x => x[0]).join('').substring(0, 2).toUpperCase();
                const avatar = document.createElement('span');
                avatar.className = 'chat-user-avatar';
                avatar.textContent = initials;
                if (u.online) {
                    const onlineDot = document.createElement('span');
                    onlineDot.className = 'online-dot';
                    li.appendChild(onlineDot);
                }
                const name = document.createElement('span');
                name.textContent = u.username;
                li.appendChild(avatar);
                li.appendChild(name);
                li.onclick = () => selectUser(u.id, u.username);
                if (selectedUserId === u.id) li.classList.add('active');
                list.appendChild(li);
            });
        })
        .catch(error => console.error('Error loading online users:', error));
    // Disable chat input and send button if no user selected
    setChatInputEnabled(!!selectedUserId);
}

function setChatInputEnabled(enabled) {
    document.getElementById('chatInput').disabled = !enabled;
    document.getElementById('sendBtn').disabled = !enabled;
}

function selectUser(userId, username) {
    selectedUserId = userId;
    selectedUserName = username;
    document.getElementById('chatMessages').innerHTML = '';
    setChatInputEnabled(true);
    fetch('chat_history.php?user_id=' + userId)
        .then(r => r.json())
        .then(msgs => {
            msgs.forEach(m => appendChatMessage(m, m.from_user_id === adminId ? 'admin' : 'user', m.username));
        })
        .catch(error => console.error('Error fetching chat history:', error));
    // Focus chat input after selecting user
    setTimeout(() => {
        document.getElementById('chatInput').focus();
    }, 100);
}

function appendChatMessage(msg, sender, senderName) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'chat-bubble ' + (sender === 'admin' ? 'admin-bubble' : 'user-bubble');
    div.innerHTML = `<span class='sender'>${sender === 'admin' ? 'You' : (senderName || selectedUserName)}</span>: ` +
        `<span class='msg'>${msg.message}</span> <span class='time'>${msg.created_at ? msg.created_at : ''}</span>`;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function setupSendBox() {
    const input = document.getElementById('chatInput');
    input.disabled = true;
    document.getElementById('sendBtn').disabled = true;
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!selectedUserId) return;
            sendMessage(input.value);
            input.value = '';
        }
    });
    document.getElementById('sendBtn').onclick = function() {
        if (!selectedUserId) return;
        sendMessage(input.value);
        input.value = '';
    };
}
setInterval(loadOnlineUsers, 10000);
window.onload = function() { loadOnlineUsers(); setupSendBox(); };
</script>
