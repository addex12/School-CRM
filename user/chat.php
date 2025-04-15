<?php
// DEBUG: Show all errors (remove after fixing)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$admin_id = 1; // Default admin user_id

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Live Chat - School CRM</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        body {
            background: #f2f6fa;
        }
        .chat-main-wrap {
            display: flex;
            max-width: 1100px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
            overflow: hidden;
            min-height: 600px;
        }
        .sidebar {
            width: 270px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            padding: 0;
            overflow-y: auto;
        }
        .sidebar h3 {
            padding: 18px 20px 10px 20px;
            margin: 0;
            font-size: 1.2em;
            color: #3498db;
            border-bottom: 1px solid #e2e8f0;
        }
        .user-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .user-list li {
            padding: 14px 20px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }
        .user-list li.active,
        .user-list li:hover {
            background: #eaf6fb;
        }
        .user-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 12px;
            background: #ccc;
            display: inline-block;
        }
        .user-status.online {
            background: #2ecc71;
        }
        .user-status.offline {
            background: #e74c3c;
        }
        .user-list .username {
            font-weight: 500;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 18px 30px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 1.1em;
            font-weight: 600;
            color: #3498db;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 25px 30px;
            background: #f9fbfd;
        }
        .message {
            margin-bottom: 18px;
            padding: 10px 16px;
            background: #e9ecef;
            border-radius: 8px;
            position: relative;
            max-width: 60%;
            clear: both;
        }
        .message.me {
            background: #d1e7fd;
            margin-left: auto;
            text-align: right;
        }
        .message .meta {
            font-size: 0.85em;
            color: #888;
            margin-bottom: 2px;
        }
        .message .content {
            font-size: 1.02em;
            color: #222;
        }
        .chat-input-wrap {
            padding: 18px 30px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .chat-input-wrap form {
            display: flex;
            gap: 10px;
        }
        .chat-input-wrap textarea {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: none;
            font-size: 1.05em;
        }
        .btn-primary {
            padding: 0 22px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .status-dot {
            margin-right: 7px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        <div class="chat-main-wrap">
            <div class="sidebar">
                <h3>Users</h3>
                <ul class="user-list" id="userList"></ul>
            </div>
            <div class="chat-area">
                <div class="chat-header" id="chatHeader">Chat</div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-wrap">
                    <form id="chatForm" autocomplete="off">
                        <textarea id="chatInput" rows="2" placeholder="Type your message..." required></textarea>
                        <button type="submit" class="btn-primary">Send</button>
                    </form>
                </div>
            </div>
        </div>
        <?php include_once __DIR__ . '/includes/footer.php'; ?>
    </div>
    <script>
        const userId = <?= json_encode($user_id) ?>;
        const username = <?= json_encode($username) ?>;
        const adminId = <?= json_encode($admin_id) ?>;
        let selectedUserId = adminId;
        let ws;
        let users = [];
        let reconnectAttempts = 0;

        function fetchUsers() {
            fetch('online_users.php') // Use unified endpoint
                .then((res) => res.json())
                .then((data) => {
                    users = data;
                    renderUserList();
                });
        }

        function fetchChatHistory() {
            fetch('chat_history.php?user_id=' + selectedUserId)
                .then((res) => res.json())
                .then((data) => {
                    renderMessages(data);
                });
        }

        function renderUserList() {
            const ul = document.getElementById('userList');
            ul.innerHTML = '';
            users.forEach((u) => {
                if (u.id == userId) return; // skip self
                const li = document.createElement('li');
                li.className = u.id == selectedUserId ? 'active' : '';
                li.onclick = () => {
                    selectedUserId = u.id;
                    renderUserList();
                    document.getElementById('chatHeader').textContent =
                        'Chat with ' + u.username;
                    fetchChatHistory();
                };
                const status = document.createElement('span');
                status.className = 'user-status ' + (u.online ? 'online' : 'offline');
                li.appendChild(status);
                const uname = document.createElement('span');
                uname.className = 'username';
                uname.textContent = u.username;
                li.appendChild(uname);
                ul.appendChild(li);
            });
        }

        function renderMessages(messages) {
            const box = document.getElementById('chatMessages');
            box.innerHTML = '';
            messages.forEach((msg) => {
                const div = document.createElement('div');
                div.className = 'message' + (msg.from_user_id == userId ? ' me' : '');
                div.innerHTML =
                    '<div class="meta">' +
                    (msg.from_user_id == userId ? 'Me' : msg.username) +
                    ' <small>' +
                    (msg.created_at ? new Date(msg.created_at).toLocaleString() : '') +
                    '</small></div>' +
                    '<div class="content">' +
                    escapeHtml(msg.message) +
                    '</div>';
                box.appendChild(div);
            });
            box.scrollTop = box.scrollHeight;
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            };
            return text.replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        }

        function connectWebSocket() {
            ws = new WebSocket('ws://' + window.location.hostname + ':8080');

            ws.onopen = function () {
                reconnectAttempts = 0;
                console.log('WebSocket connected');
                ws.send(
                    JSON.stringify({
                        type: 'auth',
                        userId: <?= $user_id ?>,
                    })
                );
            };

            ws.onerror = function (error) {
                console.error('WebSocket Error:', error);
                scheduleReconnect();
            };

            ws.onclose = function () {
                console.log('WebSocket closed');
                scheduleReconnect();
            };
        }

        function scheduleReconnect() {
            const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
            setTimeout(() => {
                reconnectAttempts++;
                connectWebSocket();
            }, delay);
        }

        document.getElementById('chatForm').onsubmit = function (e) {
            e.preventDefault();
            const msg = document.getElementById('chatInput').value.trim();
            if (!msg) return;
            if (ws && ws.readyState === 1) {
                ws.send(
                    JSON.stringify({ type: 'chat', to: selectedUserId, message: msg })
                );
                document.getElementById('chatInput').value = '';
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            fetchUsers();
            fetchChatHistory();
            connectWebSocket();
            setInterval(fetchUsers, 10000); // Refresh user list every 10s
        });
    </script>
</body>
</html>