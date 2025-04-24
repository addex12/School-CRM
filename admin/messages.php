<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$pageTitle = "Admin Messaging";

// Get all non-admin users
$users = $pdo->query("SELECT id, username FROM users WHERE role_id != 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get unread counts for each user
$unreadCounts = [];
$stmt = $pdo->query("SELECT receiver_id, COUNT(*) as unread FROM messages WHERE is_read = 0 AND receiver_id = {$_SESSION['user_id']} GROUP BY receiver_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $unreadCounts[$row['receiver_id']] = $row['unread'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .messaging-container {
            display: flex;
            height: 70vh;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
            overflow: hidden;
        }
        .contact-list {
            width: 250px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f9f9f9;
        }
        .message-form {
            padding: 15px;
            border-top: 1px solid #ddd;
            background: #fff;
        }
        .user-list li {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .user-list li:hover {
            background-color: #f5f5f5;
        }
        .user-list li.selected {
            background-color: #e9f7fe;
        }
        .unread-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        .chat-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            max-width: 70%;
            word-break: break-word;
        }
        .chat-message.own {
            background-color: #e3f2fd;
            margin-left: auto;
        }
        .chat-message.other {
            background-color: #f1f1f1;
            margin-right: auto;
        }
        .msg-time {
            font-size: 12px;
            color: #777;
            display: block;
            margin-top: 5px;
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
                border-bottom: 1px solid #ddd;
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
                border-bottom: 1px solid #ddd;
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
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header"><h1><?= htmlspecialchars($pageTitle) ?></h1></header>
            <div class="content">
                <div class="messaging-container">
                    <aside class="contact-list">
                        <h2>Users</h2>
                        <ul id="user-list" class="user-list">
                            <li data-user-id="broadcast" class="contact-item">Broadcast to All Users</li>
                            <?php foreach ($users as $user): ?>
                                <li data-user-id="<?= $user['id'] ?>" class="contact-item">
                                    <?= htmlspecialchars($user['username']) ?>
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
                            <button type="submit" class="btn btn-primary">Send</button>
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
            
            let selectedUserId = null;
            let currentUser = <?= $_SESSION['user_id'] ?? 0 ?>;
            
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
                                
                                // Scroll to bottom
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                                
                                // Mark messages as read
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
                            // Update unread count in UI
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
                
                // Update selected user
                selectedUserId = li.getAttribute('data-user-id');
                receiverInput.value = selectedUserId;
                
                // Update UI
                document.querySelectorAll('.contact-item').forEach(item => {
                    item.classList.remove('selected');
                });
                li.classList.add('selected');
                
                // Update header
                chatHeader.innerHTML = `<h3>Chat with ${li.textContent.trim()}</h3>`;
                
                // Show message form
                messageForm.style.display = 'block';
                
                // Load messages
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
        });
    </script>
</body>
</html>