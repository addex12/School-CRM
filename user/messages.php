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
        (m.receiver_id = u.id AND m.sender_id = :current_user)
    )
    WHERE u.id != :current_user
    AND u.role_id != :admin_role_id
    GROUP BY u.id, u.username, u.avatar, u.role_id, r.role_name
    ORDER BY last_message_time DESC
");

$conversations->execute([
    ':current_user' => $current_user_id,
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
        // [Previous JavaScript code remains exactly the same]
        // Only change the API endpoints to point to user-specific versions:
        const MESSAGES_API = '../api/user/get_messages.php';
        const SEND_API = '../api/user/send_message.php';
        const MARK_READ_API = '../api/user/mark_read.php';
        
        // [Rest of your existing JavaScript code]
    });
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>