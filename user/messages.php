<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$current_user_role_id = (int)($_SESSION['role_id'] ?? 0);

// Get admin role ID
$admin_role_id = $pdo->query("SELECT id FROM roles WHERE role_name = 'admin' LIMIT 1")->fetchColumn();

// Get all conversations (excluding admins)
$conversations = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.avatar,
        u.role_id,
        r.role_name,
        SUM(CASE WHEN m.is_read = 0 AND m.sender_id = u.id THEN 1 ELSE 0 END) as unread_count,
        MAX(m.sent_at) as last_message_time
    FROM users u
    JOIN roles r ON u.role_id = r.id
    JOIN messages m ON (
        (m.sender_id = u.id AND m.receiver_id = :current_user) OR
        (m.receiver_id = u.id AND m.sender_id = :current_user2)
    )
    WHERE u.id != :current_user
    AND u.role_id != :admin_role_id
    GROUP BY u.id, u.username, u.avatar, u.role_id, r.role_name
    ORDER BY last_message_time DESC
");

$conversations->execute([
    ':current_user' => $current_user_id,
    ':current_user2' => $current_user_id,
    ':admin_role_id' => $admin_role_id ?: 0
]);
$contacts = $conversations->fetchAll(PDO::FETCH_ASSOC);

// Get support contacts (simplified version)
$support_contact = $pdo->query("
    SELECT u.id, u.username, u.avatar, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name IN ('admin', 'teacher', 'support')
    ORDER BY FIELD(r.role_name, 'admin', 'teacher', 'support')
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></title>
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
        .role-badge {
            font-size: 0.7em;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
            text-transform: capitalize;
        }
        .unread-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        .support-contact {
            background-color: #f8f9fa;
            border-left: 3px solid #e74c3c;
        }
    </style>
</head>
<body>
    <?php include '../includes/user_header.php'; ?>
    
    <div class="container">
        <h1>Messages</h1>
        
        <div class="messaging-container">
            <aside class="contact-list">
                <?php if ($support_contact): ?>
                <div class="contact-item support-contact" data-user-id="<?= $support_contact['id'] ?>">
                    <img src="../assets/avatars/<?= htmlspecialchars($support_contact['avatar'] ?? 'default.jpg') ?>" 
                         class="contact-avatar" 
                         alt="<?= htmlspecialchars($support_contact['username']) ?>">
                    <div class="contact-info">
                        <div class="contact-name">
                            <?= htmlspecialchars($support_contact['username']) ?>
                            <span class="role-badge"><?= htmlspecialchars($support_contact['role_name']) ?></span>
                        </div>
                        <div class="contact-last-message">Click to message support</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php foreach ($contacts as $contact): ?>
                <div class="contact-item" data-user-id="<?= $contact['id'] ?>" data-role-id="<?= $contact['role_id'] ?>">
                    <img src="../assets/avatars/<?= htmlspecialchars($contact['avatar'] ?? 'default.jpg') ?>" 
                         class="contact-avatar" 
                         alt="<?= htmlspecialchars($contact['username']) ?>">
                    <div class="contact-info">
                        <div class="contact-name">
                            <?= htmlspecialchars($contact['username']) ?>
                            <span class="role-badge"><?= htmlspecialchars($contact['role_name']) ?></span>
                            <?php if ($contact['unread_count'] > 0): ?>
                                <span class="unread-badge"><?= $contact['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="contact-last-message">
                            Last activity: <?= date('M j, g:i a', strtotime($contact['last_message_time'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </aside>
            
            <section class="chat-section">
                <div id="chat-header" class="chat-header">
                    <h3>Select a conversation</h3>
                </div>
                <div id="chat-messages" class="chat-messages">
                    <p class="no-messages">Please select a contact to start chatting</p>
                </div>
                <form id="message-form" class="message-form" style="display:none;">
                    <input type="hidden" name="receiver_id" id="receiver_id">
                    <textarea name="message" id="message-input" rows="3" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </section>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Use correct API endpoints
        const MESSAGES_API = '../api/user/get_messages.php';
        const SEND_API = '../api/user/send_message.php';
        const MARK_READ_API = '../api/user/mark_read.php';

        // DOM elements
        const contactItems = document.querySelectorAll('.contact-item');
        const chatHeader = document.getElementById('chat-header');
        const chatMessages = document.getElementById('chat-messages');
        const messageForm = document.getElementById('message-form');
        const receiverInput = document.getElementById('receiver_id');
        const messageInput = document.getElementById('message-input');

        let selectedUserId = null;

        // Load messages for selected user
        function loadMessages(userId) {
            if (!userId) return;

            fetch(`${MESSAGES_API}?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    chatMessages.innerHTML = '';
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message ${msg.is_own ? 'sent' : 'received'}`;
                            messageDiv.innerHTML = `
                                <div>${msg.message}</div>
                                <div class="message-time">${msg.sent_at}</div>
                            `;
                            chatMessages.appendChild(messageDiv);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        markAsRead(userId);
                    } else if (data.success) {
                        chatMessages.innerHTML = '<p class="no-messages">No messages yet. Start the conversation!</p>';
                    } else {
                        chatMessages.innerHTML = `<p class="no-messages">Error loading messages: ${data.error}</p>`;
                    }
                })
                .catch(() => {
                    chatMessages.innerHTML = '<p class="no-messages">Error loading messages</p>';
                });
        }

        // Mark messages as read
        function markAsRead(senderId) {
            fetch(`${MARK_READ_API}?user_id=${senderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.querySelector(`.contact-item[data-user-id="${senderId}"] .unread-badge`);
                        if (badge) badge.remove();
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

            fetch(SEND_API, {
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
            .catch(() => {
                alert('Failed to send message');
            });
        });

        // Select user from contact list
        contactItems.forEach(item => {
            item.addEventListener('click', function() {
                selectedUserId = this.getAttribute('data-user-id');
                receiverInput.value = selectedUserId;
                contactItems.forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                const contactName = this.querySelector('.contact-name').textContent;
                chatHeader.innerHTML = `<h3>Chat with ${contactName}</h3>`;
                messageForm.style.display = 'block';
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