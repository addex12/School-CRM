<?php
require_once '../includes/auth.php';
$auth->requireLogin();

// Get current user and their role
$user = $auth->getUser();
$isAdmin = ($user['role'] === 'admin');

// Handle chat messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = htmlspecialchars(trim($_POST['message']));
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, message, is_admin) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $message, $isAdmin]);
    }
}

// Get chat messages
$stmt = $pdo->prepare("
    SELECT cm.*, u.username 
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.id
    ORDER BY cm.created_at ASC
");
$stmt->execute();
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat - Parent Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .chat-header {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: #f9f9f9;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .message-admin {
            background: #e3f2fd;
            border-left: 3px solid #3498db;
        }
        .message-user {
            background: #e8f5e9;
            border-left: 3px solid #2ecc71;
        }
        .message-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .chat-input {
            padding: 15px;
            background: white;
            border-top: 1px solid #ddd;
        }
        .chat-input textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Chat Support</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="chat-container">
            <div class="chat-header">
                <h2>Chat with <?php echo $isAdmin ? 'Users' : 'Administrators'; ?></h2>
            </div>
            
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['is_admin'] ? 'message-admin' : 'message-user'; ?>">
                        <div class="message-meta">
                            <span class="message-user"><?php echo htmlspecialchars($message['username']); ?></span>
                            <span class="message-time"><?php echo date('M j, g:i a', strtotime($message['created_at'])); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($message['message']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" class="chat-input">
                <textarea name="message" placeholder="Type your message here..." required></textarea>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Send</button>
            </form>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>