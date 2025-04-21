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
        COALESCE(SUM(CASE WHEN m.is_read = 0 AND m.sender_id = u.id THEN 1 ELSE 0 END), 0) as unread_count,
        MAX(m.sent_at) as last_message_time
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN messages m ON (
        (m.sender_id = u.id AND m.receiver_id = :current_user)
        OR
        (m.receiver_id = u.id AND m.sender_id = :current_user2)
    )
    WHERE u.id != :current_user3
    AND u.role_id != :admin_role_id
    GROUP BY u.id, u.username, u.avatar, u.role_id, r.role_name
    ORDER BY last_message_time DESC
");

$conversations->execute([
    ':current_user' => $current_user_id,
    ':current_user2' => $current_user_id,
    ':current_user3' => $current_user_id,
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
            background: #fafbfc;
        }
        .contact-list {
            width: 250px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            background: #fff;
            padding: 0;
        }
        .contact-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            transition: background 0.2s;
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
            background: #f0f0f0;
        }
        .contact-info {
            flex: 1;
            min-width: 0;
        }
        .contact-name {
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .contact-last-message {
            font-size: 0.85em;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f9f9f9;
        }
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            background: #fff;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
        }
        .message-form {
            padding: 15px;
            border-top: 1px solid #ddd;
            background: #fff;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 70%;
            word-wrap: break-word;
            display: flex;
            flex-direction: column;
        }
        .message.sent {
            background-color: #dcf8c6;
            margin-left: auto;
            border-bottom-right-radius: 0;
            align-items: flex-end;
        }
        .message.received {
            background-color: #fff;
            margin-right: auto;
            border-bottom-left-radius: 0;
            box-shadow: 0 1px 1px rgba(0,0,0,0.05);
            align-items: flex-start;
        }
        .message-content {
            word-break: break-word;
        }
        .message-meta {
            font-size: 0.75em;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }
        .no-messages {
            color: #999;
            text-align: center;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Messages</h1>
        
        <div class="messaging-container">
            <aside class="contact-list">
                <?php if (!empty($support_contact)): ?>
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
                            <?= $contact['last_message_time'] ? 'Last activity: ' . date('M j, g:i a', strtotime($contact['last_message_time'])) : '' ?>
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
    const MESSAGES_API = '../api/user/get_messages.php';
    const contactItems = document.querySelectorAll('.contact-item');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const messageForm = document.getElementById('message-form');
    const receiverInput = document.getElementById('receiver_id');
    const messageInput = document.getElementById('message-input');
    const currentUserId = <?= $current_user_id ?>;

    let selectedUserId = null;

    function loadMessages(userId) {
        if (!userId) return;
        
        fetch(`${MESSAGES_API}?user_id=${userId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                chatMessages.innerHTML = '';
                
                if (data.success && data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${msg.is_own ? 'sent' : 'received'}`;
                        messageDiv.innerHTML = `
                            <div class="message-content">${msg.message}</div>
                            <div class="message-meta">
                                <span class="message-sender">${msg.sender}</span>
                                <span class="message-time">${msg.sent_at}</span>
                            </div>
                        `;
                        chatMessages.appendChild(messageDiv);
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } else {
                    const errorMsg = data.error ? `Error: ${data.error}` : 'No messages yet';
                    chatMessages.innerHTML = `<div class="no-messages">${errorMsg}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                chatMessages.innerHTML = '<div class="no-messages">Error loading messages</div>';
            });
    }

    // Contact item click handler
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
            
            // Show message form and load messages
            messageForm.style.display = 'block';
            loadMessages(selectedUserId);
        });
    });

    // Initialize with first contact if available
    if (contactItems.length > 0) {
        contactItems[0].click();
    }
});
</script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>