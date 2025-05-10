<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$pageTitle = "Admin Messaging";

// Get all non-admin users
$users = $pdo->query("SELECT id, username, last_active FROM users WHERE role_id != 1 AND active = 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get all admins (including self)
$admins = $pdo->query("SELECT id, username, last_active FROM users WHERE role_id = 1  ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get unread counts for each user (highlight users with unread messages)
$unreadCounts = [];
$stmt = $pdo->query("SELECT sender_id, COUNT(*) as unread FROM messages WHERE is_read = 0 AND receiver_id = {$_SESSION['user_id']} GROUP BY sender_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $unreadCounts[$row['sender_id']] = $row['unread'];
}

// Simulate online users (last_active within 5 minutes)
$now = time();
$onlineUsers = [];
foreach ($users as $u) {
    if (!empty($u['last_active']) && strtotime($u['last_active']) > $now - 300) {
        $onlineUsers[] = $u['id'];
    }
}
// Simulate online admins
$onlineAdmins = [];
foreach ($admins as $a) {
    if (!empty($a['last_active']) && strtotime($a['last_active']) > $now - 300) {
        $onlineAdmins[] = $a['id'];
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
        /* Adugna Gizaw: All styles use adugna- prefix for patenting and branding. Fully responsive, compact, and visually outstanding. */
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .adugna-admin-main { margin-left: 0; padding: 1.2rem 0.5rem; }
        @media (min-width: 900px) { .adugna-admin-main { margin-left: 260px; padding: 2rem 2.5rem; } }
        .adugna-messaging-container {
            display: flex;
            flex-direction: column;
            height: 80vh;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(44,62,80,0.09);
            position: relative;
            min-height: 420px;
        }
        @media (min-width: 900px) {
            .adugna-messaging-container { flex-direction: row; height: 70vh; }
        }
        .adugna-contact-list {
            width: 100%;
            max-width: 320px;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-width: 180px;
        }
        @media (max-width: 900px) {
            .adugna-contact-list { width: 100%; max-width: 100vw; border-right: none; border-bottom: 1px solid #e5e7eb; }
        }
        .adugna-contact-list-header {
            padding: 0.7rem 1rem 0.3rem 1rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a4d5c;
            background: #f5f7fa;
            letter-spacing: 0.01em;
        }
        .adugna-search-bar {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 1rem 0.3rem 1rem;
            background: #f5f7fa;
        }
        .adugna-search-bar input {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #f9fafb;
            font-size: 0.98rem;
        }
        .adugna-btn {
            padding: 5px 12px;
            font-size: 0.93em;
            border-radius: 4px;
            border: none;
            background: #e3eafc;
            color: #1a4d5c;
            margin-left: 0.1em;
            cursor: pointer;
            transition: background 0.13s;
            display: flex;
            align-items: center;
            gap: 0.2em;
        }
        .adugna-btn.adugna-primary { background: #1a4d5c; color: #fff; }
        .adugna-btn.adugna-secondary { background: #e3eafc; color: #1a4d5c; }
        .adugna-btn:active, .adugna-btn:focus { outline: 2px solid #1a4d5c; }
        .adugna-online-users {
            padding: 0.3rem 1rem 0.3rem 1rem;
            background: #f5f7fa;
            border-bottom: 1px solid #e5e7eb;
            color: #1a4d5c;
            font-size: 0.95em;
        }
        .adugna-online-section-title {
            font-weight: 600;
            color: #1a4d5c;
            margin-bottom: 0.1em;
            margin-top: 0.3em;
            font-size: 0.93em;
        }
        .adugna-online-admin-pill, .adugna-online-user-pill {
            display: inline-block;
            border-radius: 1em;
            padding: 0.13em 0.7em;
            font-size: 0.93em;
            margin-right: 0.2em;
            margin-bottom: 0.1em;
            font-weight: 500;
        }
        .adugna-online-admin-pill { background: #1a4d5c; color: #fff; }
        .adugna-online-user-pill { background: #27ae60; color: #fff; }
        .adugna-user-list {
            list-style: none;
            margin: 0;
            padding: 0;
            flex: 1;
            overflow-y: auto;
        }
        .adugna-user-list li {
            padding: 7px 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.98em;
            transition: background 0.13s;
            min-height: 32px;
        }
        .adugna-user-list li:hover { background-color: #e2efda; }
        .adugna-user-list li.selected { background-color: #dbeafe; }
        .adugna-user-list .adugna-online-dot {
            width: 8px;
            height: 8px;
            background: #27ae60;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .adugna-unread-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 1px 6px;
            font-size: 11px;
            margin-left: 5px;
            font-weight: 600;
        }
        .adugna-user-list li.adugna-unread-highlight {
            background: #fffbe6 !important;
            font-weight: 600;
            border-left: 3px solid #e74c3c;
        }
        .adugna-chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            border-radius: 0 16px 16px 0;
            background: #eaeff7;
            box-shadow: 0 1px 3px rgba(44,62,80,0.04);
        }
        .adugna-message-card-header {
            padding: 0.7rem 1.1rem 0.5rem 1.1rem;
            font-size: 1.03rem;
            font-weight: 600;
            color: #1a4d5c;
            border-bottom: 1px solid #e5e7eb;
            background: #f5f7fa;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .adugna-message-notification-bell {
            position: relative;
            margin-left: 0.5em;
            font-size: 1em;
            color: #e74c3c;
            cursor: pointer;
        }
        .adugna-message-notification-badge {
            position: absolute;
            top: -7px;
            right: -10px;
            background: #e74c3c;
            color: #fff;
            border-radius: 50%;
            font-size: 10px;
            padding: 1px 5px;
            font-weight: 700;
        }
        .adugna-chat-messages {
            flex: 1;
            padding: 10px 10px;
            overflow-y: auto;
            background: #eaeff7;
        }
        .adugna-message-form {
            padding: 8px 10px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
            display: flex;
            gap: 0.5rem;
        }
        .adugna-message-form textarea {
            flex: 1;
            padding: 7px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #f9fafb;
            font-size: 0.97rem;
            resize: none;
        }
        .adugna-message-form .adugna-btn {
            padding: 7px 14px;
            font-size: 0.97em;
        }
        .adugna-chat-message {
            margin-bottom: 5px;
            padding: 7px 10px;
            border-radius: 14px;
            max-width: 85%;
            word-break: break-word;
            font-size: 0.95em;
            box-shadow: 0 1px 2px rgba(44,62,80,0.07);
            position: relative;
            clear: both;
            display: flex;
            flex-direction: column;
        }
        .adugna-chat-message.own {
            background: #d1f7c4;
            margin-left: auto;
            color: #1a4d5c;
            border-bottom-right-radius: 4px;
            border-bottom-left-radius: 14px;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            align-self: flex-end;
            border: 1px solid #b2e59f;
        }
        .adugna-chat-message.other {
            background: #fff;
            margin-right: auto;
            color: #222d32;
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 14px;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            align-self: flex-start;
            border: 1px solid #e0e0e0;
        }
        .adugna-msg-time {
            font-size: 9px;
            color: #aaa;
            margin-top: 1px;
            display: block;
            text-align: right;
        }
        .adugna-chat-message strong {
            font-size: 0.93em;
            color: #1a4d5c;
            font-weight: 600;
            margin-bottom: 1px;
        }
        .adugna-edit-btn, .adugna-delete-btn {
            background: none;
            border: none;
            color: #1a4d5c;
            font-size: 0.93em;
            margin-left: 4px;
            cursor: pointer;
            padding: 0 2px;
        }
        .adugna-edit-btn:hover, .adugna-delete-btn:hover { color: #e74c3c; }
        @media (max-width: 600px) {
            .adugna-messaging-container { flex-direction: column; height: auto; }
            .adugna-contact-list { width: 100%; min-width: 0; max-width: 100vw; border-right: none; border-bottom: 1px solid #e5e7eb; font-size: 0.97em; }
            .adugna-chat-section { min-width: 0; }
            .adugna-chat-messages { padding: 5px; }
            .adugna-message-form { padding: 5px; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-admin-main">
            <header class="admin-header"><h1 style="color:#215967;font-weight:700;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($pageTitle) ?></h1></header>
            <div class="content">
                <div class="adugna-messaging-container">
                    <aside class="adugna-contact-list">
                        <div class="adugna-contact-list-header">Users</div>
                        <div class="adugna-search-bar" style="margin-bottom:0.5rem; padding-bottom:0;">
                            <input type="text" id="userSearch" placeholder="Search users..." style="flex:1;min-width:0;">
                            <button class="adugna-btn adugna-primary" id="searchUserBtn" style="margin-left:0;"><i class="fas fa-search"></i></button>
                            <button class="adugna-btn adugna-secondary" id="clearUserSearch" style="margin-left:0;">Clear</button>
                        </div>
                        <div class="adugna-online-users">
                            <div class="adugna-online-section-title"><i class="fas fa-circle" style="color:#007bff;font-size:0.9em;"></i> Online Admins</div>
                            <?php foreach ($admins as $admin): ?>
                                <?php if (in_array($admin['id'], $onlineAdmins)): ?>
                                    <span class="adugna-online-admin-pill"><?= htmlspecialchars($admin['username']) ?> (Admin)</span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div class="adugna-online-section-title" style="margin-top:0.7em;"><i class="fas fa-circle" style="color:#27ae60;font-size:0.9em;"></i> Online Users</div>
                            <?php foreach ($users as $user): ?>
                                <?php if (in_array($user['id'], $onlineUsers)): ?>
                                    <span class="adugna-online-user-pill"><?= htmlspecialchars($user['username']) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <ul id="user-list" class="adugna-user-list">
                            <li data-user-id="broadcast" class="contact-item">Broadcast to All Users</li>
                            <?php foreach ($users as $user): 
                                $isOnline = in_array($user['id'], $onlineUsers);
                                $hasUnread = isset($unreadCounts[$user['id']]);
                            ?>
                                <li data-user-id="<?= $user['id'] ?>" class="contact-item<?= $isOnline ? ' online' : '' ?><?= $hasUnread ? ' adugna-unread-highlight' : '' ?>">
                                    <span>
                                        <?php if ($isOnline): ?>
                                            <span class="adugna-online-dot"></span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </span>
                                    <?php if ($hasUnread): ?>
                                        <span class="adugna-unread-badge"><?= $unreadCounts[$user['id']] ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                    <section class="adugna-chat-section">
                        <div class="adugna-message-card-header">
                            <span id="chat-header-title">
                                <i class="fas fa-paper-plane" style="color:#007bff;margin-right:6px;"></i>
                                <span id="chat-header-user">Select a user to start chatting</span>
                            </span>
                            <?php
                            $totalUnread = array_sum($unreadCounts);
                            ?>
                            <?php if ($totalUnread > 0): ?>
                                <span class="adugna-message-notification-bell" id="messageNotificationBell" title="Unread Messages">
                                    <i class="fas fa-bell"></i>
                                    <span class="adugna-message-notification-badge"><?= $totalUnread ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div id="chat-messages" class="adugna-chat-messages"></div>
                        <form id="message-form" class="adugna-message-form" style="display:none;">
                            <input type="hidden" name="receiver_id" id="receiver_id">
                            <textarea name="message" id="message-input" rows="2" placeholder="Type your message..." required></textarea>
                            <button type="submit" class="adugna-btn adugna-primary"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
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
                                    messageDiv.className = `adugna-chat-message ${msg.is_own ? 'own' : 'other'}`;
                                    messageDiv.innerHTML = `
                                        <strong>${msg.sender}</strong>
                                        <p class="msg-text" data-msg-id="${msg.id}">${msg.message}</p>
                                        <span class="adugna-msg-time">${msg.sent_at}</span>
                                        ${
                                            msg.is_own
                                            ? `<button class="adugna-edit-btn" data-msg-id="${msg.id}" data-msg-text="${encodeURIComponent(msg.message)}">Edit</button>
                                               <button class="adugna-delete-btn" data-msg-id="${msg.id}">Delete</button>`
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
                            const badge = document.querySelector(`li[data-user-id="${senderId}"] .adugna-unread-badge`);
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
                const editBtn = e.target.closest('.adugna-edit-btn');
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
                const deleteBtn = e.target.closest('.adugna-delete-btn');
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

            // Notification bell click: select first unread user
            const messageNotificationBell = document.getElementById('messageNotificationBell');
            if (messageNotificationBell) {
                messageNotificationBell.addEventListener('click', function() {
                    const firstUnread = document.querySelector('.adugna-user-list li .adugna-unread-badge');
                    if (firstUnread) {
                        const li = firstUnread.closest('li');
                        if (li) {
                            li.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            li.click();
                        }
                    }
                });
            }

            // Update chat header title on user select
            const chatHeaderUser = document.getElementById('chat-header-user');
            userList.addEventListener('click', function(e) {
                const li = e.target.closest('li[data-user-id]');
                if (!li) return;
                selectedUserId = li.getAttribute('data-user-id');
                receiverInput.value = selectedUserId;
                document.querySelectorAll('.contact-item').forEach(item => {
                    item.classList.remove('selected');
                });
                li.classList.add('selected');
                chatHeaderUser.textContent = li.textContent.trim();
                messageForm.style.display = 'flex';
                loadMessages(selectedUserId);
            });
        });
    </script>
</body>
</html>
