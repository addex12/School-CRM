<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

// Get messages
$stmt = $pdo->prepare("
    SELECT m.*, u.username as sender_name 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE receiver_id = ?
    ORDER BY sent_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbox - User Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .inbox-container { max-width: 1000px; margin: 20px auto; }
        .message-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .message-tab { padding: 10px 20px; cursor: pointer; }
        .message-item { 
            padding: 15px; margin-bottom: 10px; 
            background: #f8f9fa; border-radius: 5px;
            display: flex; justify-content: space-between;
        }
        .unread { background: #e3f2fd; font-weight: bold; }
        .message-preview { max-width: 70%; }
    </style>
</head>
<body>
    <?php include 'includes/user_header.php'; ?>
    
    <div class="inbox-container">
        <h1>Your Inbox</h1>
        
        <div class="message-tabs">
            <button class="message-tab active" data-type="all">All Messages</button>
            <button class="message-tab" data-type="email">Emails</button>
            <button class="message-tab" data-type="chat">Chats</button>
            <button class="message-tab" data-type="unread">Unread</button>
        </div>

        <div class="message-list">
            <?php foreach ($messages as $msg): ?>
                <a href="view_message.php?id=<?= $msg['id'] ?>" class="message-item <?= !$msg['is_read'] ? 'unread' : '' ?>">
                    <div class="message-preview">
                        <h3><?= htmlspecialchars($msg['subject']) ?></h3>
                        <p><?= substr(htmlspecialchars($msg['content']), 0, 100) ?>...</p>
                    </div>
                    <div class="message-meta">
                        <span><?= htmlspecialchars($msg['sender_name']) ?></span>
                        <span><?= date('M j, Y g:i a', strtotime($msg['sent_at'])) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="inbox-actions">
            <a href="compose.php" class="btn btn-primary">Compose New</a>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.message-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.message-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const type = this.dataset.type;
                // Implement filtering logic here
            });
        });
    </script>
</body>
</html>