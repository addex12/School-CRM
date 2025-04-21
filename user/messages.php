<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "My Messages";
$current_user_id = $_SESSION['user_id'];

// Get conversation partners and unread counts
$conversations = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.avatar,
        COUNT(CASE WHEN m.is_read = 0 AND m.sender_id = u.id THEN 1 END) as unread_count,
        MAX(m.sent_at) as last_message_time
    FROM users u
    JOIN messages m ON (
        (m.sender_id = u.id AND m.receiver_id = :current_user) OR
        (m.receiver_id = u.id AND m.sender_id = :current_user2)
    )
    WHERE u.id != :current_user
    GROUP BY u.id, u.username, u.avatar
    ORDER BY last_message_time DESC
");
$conversations->execute([
    ':current_user' => $current_user_id,
    ':current_user2' => $current_user_id
]);
$contacts = $conversations->fetchAll(PDO::FETCH_ASSOC);

// Get admin user for support messages
$admin_user = $pdo->query("SELECT id, username FROM users WHERE role_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .messaging-container {
            display: flex;
            height: 70vh;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .contact-list {
            width: 250px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
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
            background: white;
        }
        .contact-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        .contact-item:hover {
            background-color: #f5f5f5;
        }
        .contact-item.selected {
            background-color: #e9f7fe;
        }
        .contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .contact-info {
            flex: 1;
        }
        .contact-name {
            font-weight: bold;
        }
        .contact-last-message {
            font-size: 0.8em;
            color: #777;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .unread-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 70%;
            word-wrap: break-word;
        }
        .message.sent {
            background-color: #dcf8c6;
            margin-left: auto;
            border-bottom-right-radius: 0;
        }
        .message.received {
            background-color: #fff;
            margin-right: auto;
            border-bottom-left-radius: 0;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .message-time {
            font-size: 0.75em;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }
        #message-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            resize: none;
        }
        .no-messages {
            color: #999;
            text-align: center;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <?php include '../includes/user_header.php'; ?>
    
    <div class="container">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        
        <div class="messaging-container">
            <aside class="contact-list">
                <div class="contact-item" data-user-id="<?= $admin_user['id'] ?>">
                    <img src="../assets/avatars/<?= htmlspecialchars($admin_user['avatar'] ?? 'default.jpg') ?>" 
                         class="contact-avatar" 
                         alt="Admin Avatar">
                    <div class="contact-info">
                        <div class="contact-name">Support Team</div>
                        <div class="contact-last-message">Click to message support</div>
                    </div>
                </div>
                
                <?php foreach ($contacts as $contact): ?>
                <div class="contact-item" data-user-id="<?= $contact['id'] ?>">
                    <img src="../assets/avatars/<?= htmlspecialchars($contact['avatar'] ?? 'default.jpg') ?>" 
                         class="contact-avatar" 
                         alt="<?= htmlspecialchars($contact['username']) ?>">
                    <div class="contact-info">
                        <div class="contact-name">
                            <?= htmlspecialchars($contact['username']) ?>
                            <?php if ($contact['unread_count'] > 0): ?>
                                <span class="unread-badge"><?= $contact['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="contact-last-message">Last message</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </aside>
            
            <section class="chat-section">
                <div id="chat-header" class="chat-header">
                    <h3>Select a conversation</h3>
                </div>
                <div id="chat-messages" class="chat-messages">
                    <p class="no-messages">Please select a conversation from the list</p>
                </div>
                <form id="message-form" class="message-form" style="display:none;">
                    <input type="hidden" name="receiver_id" id="receiver_id">
                    <textarea name="message" id="message-input" rows="2" 
                              placeholder="Type your message..." required></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top:10px;">Send</button>
                </form>
            </section>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const contactItems = document.querySelectorAll('.contact-item');
        const chatHeader = document.getElementById('chat-header');
        const chatMessages = document.getElementById('chat-messages');
        const messageForm = document.getElementById('message-form');
        const receiverInput = document.getElementById('receiver_id');
        const messageInput = document.getElementById('message-input');
        
        let selectedUserId = null;
        const currentUserId = <?= $current_user_id ?>;
        
        // Load messages for selected user
        function loadMessages(userId) {
            if (!userId) return;
            
            fetch(`../api/user/get_messages.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        chatMessages.innerHTML = '';
                        
                        if (data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `message ${msg.is_own ? 'sent' : 'received'}`;
                                messageDiv.innerHTML = `
                                    <div>${msg.message}</div>
                                    <div class="message-time">${msg.sent_at}</div>
                                `;
                                chatMessages.appendChild(messageDiv);
                            });
                            
                            // Scroll to bottom
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            
                            // Mark messages as read
                            markAsRead(userId);
                        } else {
                            chatMessages.innerHTML = '<p class="no-messages">No messages yet. Start the conversation!</p>';
                        }
                    } else {
                        chatMessages.innerHTML = `<p class="no-messages">Error loading messages: ${data.error}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    chatMessages.innerHTML = '<p class="no-messages">Error loading messages</p>';
                });
        }
        
        // Mark messages as read
        function markAsRead(senderId) {
            fetch(`../api/user/mark_read.php?user_id=${senderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update unread count in UI
                        const badge = document.querySelector(`.contact-item[data-user-id="${senderId}"] .unread-badge`);
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
            
            fetch('../api/user/send_message.php', {
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
        
        // Select user from contact list
        contactItems.forEach(item => {
            item.addEventListener('click', function() {
                selectedUserId = this.getAttribute('data-user-id');
                receiverInput.value = selectedUserId;
                
                // Update UI
                contactItems.forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                
                // Update header
                const contactName = this.querySelector('.contact-name').textContent;
                chatHeader.innerHTML = `<h3>Chat with ${contactName}</h3>`;
                
                // Show message form
                messageForm.style.display = 'block';
                
                // Load messages
                loadMessages(selectedUserId);
            });
        });
        
        // Poll for new messages every 5 seconds
        setInterval(() => {
            if (selectedUserId) {
                loadMessages(selectedUserId);
            }
        }, 5000);
    });
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>