<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

// Handle chat message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, message) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $message]);
        $success = "Message sent successfully!";
    } catch (PDOException $e) {
        $error = "Error sending message: " . $e->getMessage();
    }
}

// Get chat history
$stmt = $pdo->query("SELECT c.*, u.username 
                    FROM chat_messages c 
                    JOIN users u ON c.user_id = u.id 
                    ORDER BY c.created_at DESC 
                    LIMIT 50");
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Chat</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'header.php'; ?>

        <div class="chat-container">
            <h2>Live Chat Support</h2>
            
            <?php if(isset($success)): ?>
                <div class="success-message"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <div class="chat-messages">
                <?php foreach($messages as $message): ?>
                    <div class="message">
                        <strong><?= htmlspecialchars($message['username']) ?>:</strong>
                        <?= htmlspecialchars($message['message']) ?>
                        <small><?= date('M j, g:i a', strtotime($message['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <textarea name="message" rows="3" placeholder="Type your message..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</body>
</html>