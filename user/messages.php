<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireLogin();

$pageTitle = "Messaging";

// Get all non-admin users
$users = $pdo->query("SELECT id, username FROM users WHERE role_id != 0 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

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
    <title><?= htmlspecialchars($pageTitle) ?> - Users Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 30px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 30px 30px 20px 30px;
        }
        .messaging-container {
            display: flex;
            height: 65vh;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f8fafc;
            margin-top: 20px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
        }
        .contact-list {
            width: 250px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            background: #f8fafd;
            border-radius: 8px 0 0 8px;
        }
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 0 8px 8px 0;
        }
        .chat-header {
            padding: 18px 20px 10px 20px;
            border-bottom: 1px solid #eee;
            background: #f7fafd;
            border-radius: 0 8px 0 0;
        }
        .chat-messages {
            flex: 1;
            padding: 18px 20px 18px 20px;
            overflow-y: auto;
            background: #fff;
        }
        .message-form {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            background: #f7fafd;
            border-radius: 0 0 8px 0;
        }
        .user-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .user-list li {
            padding: 12px 18px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 16px;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .user-list li:hover {
            background-color: #f0f7fa;
        }
        .user-list li.selected {
            background-color: #e9f7fe;
            font-weight: 600;
        }
        .unread-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            margin-left: 7px;
            vertical-align: middle;
        }
        .chat-message {
            margin-bottom: 15px;
            padding: 10px 14px;
            border-radius: 8px;
            max-width: 70%;
            word-break: break-word;
            font-size: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .chat-message.own {
            background-color: #e3f2fd;
            margin-left: auto;
            border: 1px solid #b6e0fe;
        }
        .chat-message.other {
            background-color: #f1f1f1;
            margin-right: auto;
            border: 1px solid #e0e0e0;
        }
        .msg-time {
            font-size: 12px;
            color: #777;
            display: block;
            margin-top: 5px;
        }
        @media (max-width: 900px) {
            .container {
                padding: 10px;
            }
            .messaging-container {
                flex-direction: column;
                height: auto;
            }
            .contact-list {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
                border-radius: 8px 8px 0 0;
            }
            .chat-section {
                border-radius: 0 0 8px 8px;
            }
        }
        @media (max-width: 600px) {
            .container {
                padding: 2vw;
            }
            .chat-header, .chat-messages, .message-form {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <header style="margin-bottom: 18px;">
            <h1 style="font-size: 2rem; font-weight: 700; margin: 0; color:#007bff;">
                <i class="fas fa-comments"></i> <?= htmlspecialchars($pageTitle) ?>
            </h1>
        </header>
        <div class="messaging-container">
            <aside class="contact-list">
                <h2 style="font-size: 1.2rem; font-weight: 600; margin: 18px 0 10px 18px; color:#007bff;">
                    <i class="fas fa-users"></i> Users
                </h2>
                <ul id="user-list" class="user-list">
                    <?php foreach ($users as $user): ?>
                        <li data-user-id="<?= $user['id'] ?>" class="contact-item">
                            <span><?= htmlspecialchars($user['username']) ?></span>
                            <?php if (isset($unreadCounts[$user['id']])): ?>
                                <span class="unread-badge"><?= $unreadCounts[$user['id']] ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
            <section class="chat-section">
                <div id="chat-header" class="chat-header">
                    <h3 style="margin:0; font-size:1.1rem; color:#333;">
                        <i class="fas fa-comment-dots"></i> Select a user to start chatting
                    </h3>
                </div>
                <div id="chat-messages" class="chat-messages"></div>
                <form id="message-form" class="message-form" style="display:none;">
                    <input type="hidden" name="receiver_id" id="receiver_id">
                    <textarea name="message" id="message-input" rows="3" placeholder="Type your message..." required style="width:100%;resize:vertical;"></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </form>
            </section>
        </div>
    </div>
    <?php include_once __DIR__ . '/includes/footer.php'; ?>
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