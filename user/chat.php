<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';
requireLogin();

// Start new thread or continue existing
$thread_id = $_GET['thread_id'] ?? null;

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $attachment = handleFileUpload('attachment');
    
    try {
        if (!$thread_id) {
            // Create new thread
            $stmt = $pdo->prepare("INSERT INTO chat_threads (user_id, subject) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], "Support Request"]);
            $thread_id = $pdo->lastInsertId();
        }

        // Insert message
        $stmt = $pdo->prepare("INSERT INTO chat_messages 
                            (thread_id, user_id, message, attachment, is_admin) 
                            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$thread_id, $_SESSION['user_id'], $message, $attachment, 0]);
        
        // Notify admins
        $subject = "New Chat Message Received";
        $body = "You have a new chat message:\n\n$message";
        sendEmailToAdmins($subject, $body);
        
        $success = "Message sent successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get chat history
$threads = $pdo->prepare("SELECT ct.*, COUNT(cm.id) as message_count 
                        FROM chat_threads ct
                        LEFT JOIN chat_messages cm ON ct.id = cm.thread_id
                        WHERE ct.user_id = ?
                        GROUP BY ct.id
                        ORDER BY ct.created_at DESC");
$threads->execute([$_SESSION['user_id']]);

if ($thread_id) {
    $messages = $pdo->prepare("SELECT cm.*, u.username 
                             FROM chat_messages cm
                             JOIN users u ON cm.user_id = u.id
                             WHERE thread_id = ?
                             ORDER BY cm.created_at ASC");
    $messages->execute([$thread_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Chat</title>
    <?php include 'includes/header.php'; ?>
    <style>
        .chat-container { max-width: 1000px; margin: 20px auto; }
        .chat-threads { width: 30%; float: left; }
        .chat-messages { width: 65%; float: right; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 5px; }
        .user-message { background: #e3f2fd; }
        .admin-message { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>

        <div class="chat-container">
            <h2>Support Chat</h2>
            
            <div class="chat-threads">
                <h3>Your Conversations</h3>
                <?php foreach ($threads as $thread): ?>
                    <div class="thread <?= $thread['id'] == $thread_id ? 'active' : '' ?>">
                        <a href="chat.php?thread_id=<?= $thread['id'] ?>">
                            <?= date('M d, H:i', strtotime($thread['created_at'])) ?> - 
                            <?= $thread['subject'] ?> (<?= $thread['message_count'] ?>)
                        </a>
                    </div>
                <?php endforeach; ?>
                <button class="btn" onclick="startNewChat()">New Conversation</button>
            </div>

            <?php if ($thread_id): ?>
            <div class="chat-messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?= $msg['is_admin'] ? 'admin-message' : 'user-message' ?>">
                        <strong><?= htmlspecialchars($msg['username']) ?>:</strong>
                        <?= htmlspecialchars($msg['message']) ?>
                        <?php if ($msg['attachment']): ?>
                            <div class="attachment">
                                <a href="../uploads/<?= $msg['attachment'] ?>" target="_blank">
                                    <i class="fas fa-paperclip"></i> Attachment
                                </a>
                            </div>
                        <?php endif; ?>
                        <small><?= date('M j, H:i', strtotime($msg['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <textarea name="message" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <input type="file" name="attachment">
                    </div>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script>
    function startNewChat() {
        window.location.href = 'chat.php';
    }
    </script>
</body>
</html>