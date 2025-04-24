<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$pageTitle = "Admin Messaging";

// Get all non-admin users
$users = $pdo->query("SELECT id, username, last_active FROM users WHERE role_id != 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get unread counts for each user
$unreadCounts = [];
$stmt = $pdo->query("SELECT receiver_id, COUNT(*) as unread FROM messages WHERE is_read = 0 AND receiver_id = {$_SESSION['user_id']} GROUP BY receiver_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $unreadCounts[$row['receiver_id']] = $row['unread'];
}

// Simulate online users (last_active within 5 minutes)
$now = time();
$onlineUsers = [];
foreach ($users as $u) {
    if (!empty($u['last_active']) && strtotime($u['last_active']) > $now - 300) {
        $onlineUsers[] = $u['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .messaging-container {
            display: flex;
            height: 70vh;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
        }
        .contact-list {
            width: 270px;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        .contact-list-header {
            padding: 1rem 1.2rem 0.5rem 1.2rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #215967;
            background: #f5f7fa;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.2rem 0.5rem 1.2rem;
            background: #f5f7fa;
        }
        .search-bar input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
        }
        .search-bar .erpnext-btn {
            padding: 8px 16px;
            font-size: 1em;
        }
        .online-users {
            padding: 0.5rem 1.2rem 0.5rem 1.2rem;
            background: #e2efda;
            border-bottom: 1px solid #e5e7eb;
            color: #215967;
            font-size: 0.98em;
        }
        .online-user-pill {
            display: inline-block;
            background: #27ae60;
            color: #fff;
            border-radius: 1em;
            padding: 0.2em 0.9em;
            font-size: 0.97em;
            margin-right: 0.4em;
        }
        .user-list {
            list-style: none;
            margin: 0;
            padding: 0;
            flex: 1;
            overflow-y: auto;
        }
        .user-list li {
            padding: 10px 1.2rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1em;
            transition: background 0.13s;
        }
        .user-list li:hover {
            background-color: #e2efda;
        }
        .user-list li.selected {
            background-color: #dbeafe;
        }
        .user-list .online-dot {
            width: 10px;
            height: 10px;
            background: #27ae60;
            border-radius: 50%;
            display: inline-block;
            margin-right: 7px;
        }
        .unread-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            margin-left: 7px;
            font-weight: 600;
        }
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .chat-header {
            padding: 1rem 1.5rem 0.7rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #215967;
            border-bottom: 1px solid #e5e7eb;
            background: #f5f7fa;
        }
        .chat-messages {
            flex: 1;
            padding: 15px 18px;
            overflow-y: auto;
            background: #f9f9f9;
        }
        .message-form {
            padding: 15px 18px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
            display: flex;
            gap: 1rem;
        }
        .message-form textarea {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
            resize: none;
        }
        .message-form .erpnext-btn {
            padding: 10px 22px;
            font-size: 1em;
        }
        .chat-message {
            margin-bottom: 15px;
            padding: 10px 14px;
            border-radius: 7px;
            max-width: 70%;
            word-break: break-word;
            font-size: 1em;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
        }
        .chat-message.own {
            background-color: #e2efda;
            margin-left: auto;
            color: #215967;
        }
        .chat-message.other {
            background-color: #f1f1f1;
            margin-right: auto;
            color: #222d32;
        }
        .msg-time {
            font-size: 12px;
            color: #777;
            display: block;
            margin-top: 5px;
        }
        .edit-btn, .delete-btn {
            background: none;
            border: none;
            color: #3b82f6;
            font-size: 0.98em;
            margin-left: 8px;
            cursor: pointer;
        }
        .edit-btn:hover, .delete-btn:hover {
            color: #e74c3c;
        }
        @media (max-width: 900px) {
            .messaging-container {
                flex-direction: column;
                height: auto;
                min-height: 400px;
            }
            .contact-list {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                min-height: 60px;
                max-height: 120px;
            }
            .chat-section {
                min-width: 0;
            }
        }
        @media (max-width: 600px) {
            .messaging-container {
                flex-direction: column;
                height: auto;
            }
            .contact-list {
                width: 100%;
                min-width: 0;
                max-width: 100vw;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                font-size: 0.98em;
            }
            .chat-section {
                min-width: 0;
            }
            .chat-messages {
                padding: 8px;
            }
            .message-form {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header"><h1 style="color:#215967;font-weight:700;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($pageTitle) ?></h1></header>
            <div class="content">
                <div class="messaging-container">
                    <aside class="contact-list">
                        <div class="contact-list-header">Users</div>
                        <div class="search-bar" style="margin-bottom:0.5rem; padding-bottom:0;">
                            <input type="text" id="userSearch" placeholder="Search users..." style="flex:1;min-width:0;">
                            <button class="erpnext-btn btn-primary" id="searchUserBtn" style="margin-left:0;"><i class="fas fa-search"></i></button>
                            <button class="erpnext-btn btn-secondary" id="clearUserSearch" style="margin-left:0;">Clear</button>
                        </div>
                        <div class="online-users">
                            <i class="fas fa-circle" style="color:#27ae60;font-size:0.9em;"></i>
                            Online:
                            <?php foreach ($users as $user): ?>
                                <?php if (in_array($user['id'], $onlineUsers)): ?>
                                    <span class="online-user-pill"><?= htmlspecialchars($user['username']) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <ul id="user-list" class="user-list">
                            <li data-user-id="broadcast" class="contact-item">Broadcast to All Users</li>
                            <?php foreach ($users as $user): ?>
                                <li data-user-id="<?= $user['id'] ?>" class="contact-item<?= in_array($user['id'], $onlineUsers) ? ' online' : '' ?>">
                                    <span>
                                        <?php if (in_array($user['id'], $onlineUsers)): ?>
                                            <span class="online-dot"></span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </span>
                                    <?php if (isset($unreadCounts[$user['id']])): ?>
                                        <span class="unread-badge"><?= $unreadCounts[$user['id']] ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                    <section class="chat-section">
                        <div id="chat-header" class="chat-header">
                            <h3>Select a user to start chatting</h3>
                        </div>
                        <div id="chat-messages" class="chat-messages"></div>
                        <form id="message-form" class="message-form" style="display:none;">
                            <input type="hidden" name="receiver_id" id="receiver_id">
                            <textarea name="message" id="message-input" rows="3" placeholder="Type your message..." required></textarea>
                            <button type="submit" class="erpnext-btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userList = document.getElementById('user-list');
            const chatHeader = document.getElementById('chat-header');
            const chatMessages = document.getElementById('chat-messages');
            const messageForm = document.getElementById('message-form');
            const receiverInput = document.getElementById('receiver_id');
            const messageInput = document.getElementById('message-input');
            const userSearch = document.getElementById('userSearch');
            const searchUserBtn = document.getElementById('searchUserBtn');
            const clearUserSearch = document.getElementById('clearUserSearch');

            let selectedUserId = null;
            let currentUser = <?= $_SESSION['user_id'] ?? 0 ?>;

            // Prepare users data for search
            const usersData = <?= json_encode($users) ?>;

            // Real-time user search/filter
            function filterUserList() {
                const val = userSearch.value.toLowerCase();
                userList.querySelectorAll('li.contact-item').forEach(function(li) {
                    if (li.dataset.userId === "broadcast") return; // always show broadcast
                    const username = li.textContent.toLowerCase();
                    li.style.display = username.includes(val) ? '' : 'none';
                });
            }
            userSearch.addEventListener('input', filterUserList);
            searchUserBtn.addEventListener('click', function(e) {
                e.preventDefault();
                filterUserList();
            });
            clearUserSearch.addEventListener('click', function(e) {
                e.preventDefault();
                userSearch.value = '';
                filterUserList();
            });

            // Load messages for selected user
            function loadMessages(userId) {
                if (!userId) return;
                fetch(`../api/get_messages.php?user_id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            chatMessages.innerHTML = '';
                            if (data.messages.length > 0) {
                                data.messages.forEach(msg => {
                                    const messageDiv = document.createElement('div');
                                    messageDiv.className = `chat-message ${msg.is_own ? 'own' : 'other'}`;
                                    messageDiv.innerHTML = `
                                        <strong>${msg.sender}</strong>
                                        <p class="msg-text" data-msg-id="${msg.id}">${msg.message}</p>
                                        <span class="msg-time">${msg.sent_at}</span>
                                        ${
                                            msg.is_own
                                            ? `<button class="edit-btn" data-msg-id="${msg.id}" data-msg-text="${encodeURIComponent(msg.message)}">Edit</button>
                                               <button class="delete-btn" data-msg-id="${msg.id}">Delete</button>`
                                            : ''
                                        }
                                    `;
                                    chatMessages.appendChild(messageDiv);
                                });
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                                markAsRead(userId);
                            } else {
                                chatMessages.innerHTML = '<p>No messages yet. Start the conversation!</p>';
                            }
                        } else {
                            chatMessages.innerHTML = `<p>Error loading messages: ${data.error}</p>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        chatMessages.innerHTML = '<p>Error loading messages</p>';
                    });
            }

            // Mark messages as read
            function markAsRead(senderId) {
                if (senderId === 'broadcast') return;
                fetch(`../api/mark_read.php?user_id=${senderId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const badge = document.querySelector(`li[data-user-id="${senderId}"] .unread-badge`);
                            if (badge) {
                                badge.remove();
                            }
                        }
                    });
            }

            // Send message
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (!message || !selectedUserId) return;
                const formData = new FormData();
                formData.append('receiver_id', selectedUserId);
                formData.append('message', message);
                fetch('../api/send_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        loadMessages(selectedUserId);
                    } else {
                        alert('Failed to send message: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to send message');
                });
            });

            // Select user from list
            userList.addEventListener('click', function(e) {
                const li = e.target.closest('li[data-user-id]');
                if (!li) return;
                selectedUserId = li.getAttribute('data-user-id');
                receiverInput.value = selectedUserId;
                document.querySelectorAll('.contact-item').forEach(item => {
                    item.classList.remove('selected');
                });
                li.classList.add('selected');
                chatHeader.innerHTML = `<h3>Chat with ${li.textContent.trim()}</h3>`;
                messageForm.style.display = 'flex';
                loadMessages(selectedUserId);
            });

            // Poll for new messages every 5 seconds
            setInterval(() => {
                if (selectedUserId) {
                    loadMessages(selectedUserId);
                }
            }, 5000);

            // The following endpoints are used for message CRUD via AJAX:
            //   - ../api/edit_message.php
            //   - ../api/delete_message.php

            // Handle Edit and Delete actions with robust event delegation
            chatMessages.addEventListener('click', function(e) {
                // Edit message
                const editBtn = e.target.closest('.edit-btn');
                if (editBtn) {
                    e.preventDefault();
                    const msgId = editBtn.getAttribute('data-msg-id');
                    const oldText = decodeURIComponent(editBtn.getAttribute('data-msg-text'));
                    const newText = prompt('Edit your message:', oldText);
                    if (newText !== null && newText.trim() !== '' && newText !== oldText) {
                        fetch('../api/edit_message.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: msgId, message: newText })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                loadMessages(selectedUserId);
                            } else {
                                alert('Failed to edit message: ' + (data.error || 'Unknown error'));
                            }
                        });
                    }
                    return;
                }
                // Delete message
                const deleteBtn = e.target.closest('.delete-btn');
                if (deleteBtn) {
                    e.preventDefault();
                    const msgId = deleteBtn.getAttribute('data-msg-id');
                    if (confirm('Are you sure you want to delete this message?')) {
                        fetch('../api/delete_message.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: msgId })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                loadMessages(selectedUserId);
                            } else {
                                alert('Failed to delete message: ' + (data.error || 'Unknown error'));
                            }
                        });
                    }
                    return;
                }
            });

            // Show online users at the top by default
            // Move online users to top of the list
            function moveOnlineUsersToTop() {
                const ul = document.getElementById('user-list');
                const online = [];
                const offline = [];
                ul.querySelectorAll('li.contact-item:not([data-user-id="broadcast"])').forEach(function(li) {
                    if (li.classList.contains('online')) {
                        online.push(li);
                    } else {
                        offline.push(li);
                    }
                });
                // Remove all except broadcast
                ul.querySelectorAll('li.contact-item:not([data-user-id="broadcast"])').forEach(li => li.remove());
                // Add online first, then offline
                const broadcast = ul.querySelector('li[data-user-id="broadcast"]');
                online.forEach(li => ul.appendChild(li));
                offline.forEach(li => ul.appendChild(li));
                if (broadcast) ul.insertBefore(broadcast, ul.firstChild);
            }
            moveOnlineUsersToTop();
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>