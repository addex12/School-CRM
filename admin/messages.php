<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$pageTitle = "Admin Messaging";
$isAdmin = ($_SESSION['role_id'] ?? 0) === 1; // Changed to role_id to match your DB

// Get all non-admin users
$users = $pdo->query("SELECT id, username, last_active FROM users WHERE role_id != 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get unread counts for each user (messages sent to admin)
$unreadCounts = [];
$admin_id = $_SESSION['user_id'] ?? null; // Changed from sender_id to user_id
if ($admin_id) {
    $stmt = $pdo->prepare("SELECT sender_id, COUNT(*) as unread FROM messages WHERE is_read = 0 AND receiver_id = :admin_id GROUP BY sender_id");
    $stmt->execute(['admin_id' => $admin_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unreadCounts[$row['sender_id']] = $row['unread'];
    }
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
    <!-- Rest of the head section remains the same -->
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
            let currentUser = <?= $_SESSION['user_id'] ?? 0 ?>; // Changed from sender_id to user_id
            let isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

            // Prepare users data for search
            const usersData = <?= json_encode($users) ?>;

            // Real-time user search/filter
            function filterUserList() {
                const val = userSearch.value.toLowerCase();
                userList.querySelectorAll('li.contact-item').forEach(function(li) {
                    if (li.dataset.userId === "broadcast") return;
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
                
                const formData = new FormData();
                formData.append('contact_id', userId);
                
                fetch('../api/get_messages.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        chatMessages.innerHTML = '';
                        if (data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `chat-message ${msg.is_own ? 'own' : 'other'}`;
                                let showEdit = msg.is_own || isAdmin;
                                messageDiv.innerHTML = `
                                    <strong>${msg.sender}</strong>
                                    <p class="msg-text" data-msg-id="${msg.id}">${msg.content}</p>
                                    <span class="msg-time">${msg.sent_at}</span>
                                    ${
                                        showEdit
                                        ? `<button class="edit-btn" data-msg-id="${msg.id}" data-msg-text="${encodeURIComponent(msg.content)}">Edit</button>
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
                fetch(`../api/mark_read.php?sender_id=${senderId}`)
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
                formData.append('content', message);
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

            // Handle Edit and Delete actions
            chatMessages.addEventListener('click', function(e) {
                // Edit message
                const editBtn = e.target.closest('.edit-btn');
                if (editBtn) {
                    e.preventDefault();
                    const msgId = editBtn.getAttribute('data-msg-id');
                    let oldText = '';
                    try {
                        oldText = decodeURIComponent(editBtn.getAttribute('data-msg-text'));
                    } catch (err) {
                        oldText = '';
                    }
                    const newText = prompt('Edit your message:', oldText);
                    if (newText !== null && newText.trim() !== '' && newText !== oldText) {
                        const formData = new FormData();
                        formData.append('id', msgId);
                        formData.append('content', newText);
                        
                        fetch('../api/edit_message.php', {
                            method: 'POST',
                            body: formData
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
                        const formData = new FormData();
                        formData.append('id', msgId);
                        
                        fetch('../api/delete_message.php', {
                            method: 'POST',
                            body: formData
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
