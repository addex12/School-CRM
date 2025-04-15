<?php
// DEBUG: Show all errors (remove after fixing)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

// Handle chat message submission
$admin_id = 1; // Change if your admin user_id is different
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    try {
        // Check if chat_threads table exists
        $result = $pdo->query("SHOW TABLES LIKE 'chat_threads'");
        if ($result && $result->rowCount() > 0) {
            // 1. Find if a thread exists for this user
            $stmt = $pdo->prepare("SELECT id FROM chat_threads WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $thread = $stmt->fetch();
            if (!$thread) {
                // 2. Create thread if not exists
                $stmt = $pdo->prepare("INSERT INTO chat_threads (user_id, subject, status) VALUES (?, 'General', 'open')");
                $stmt->execute([$user_id]);
                $thread_id = $pdo->lastInsertId();
            } else {
                $thread_id = $thread['id'];
            }
            // 3. Insert the message with thread_id, from_user_id, to_user_id, message
            $stmt = $pdo->prepare("INSERT INTO chat_messages (thread_id, from_user_id, to_user_id, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$thread_id, $user_id, $admin_id, $message]);
        } else {
            // Fallback to old logic if chat_threads doesn't exist
            $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, message) VALUES (?, ?)");
            $stmt->execute([$user_id, $message]);
        }
        $success = "Message sent successfully!";
    } catch (PDOException $e) {
        $error = "Error sending message: " . $e->getMessage();
    }
}

// Get chat history for this user-admin thread (if chat_threads exists)
$admin_id = 1; // Change if your admin user_id is different
$user_id = $_SESSION['user_id'];
try {
    $result = $pdo->query("SHOW TABLES LIKE 'chat_threads'");
    if ($result && $result->rowCount() > 0) {
        // Find thread for this user
        $stmt = $pdo->prepare("SELECT id FROM chat_threads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $thread = $stmt->fetch();
        if ($thread) {
            $thread_id = $thread['id'];
            $stmt = $pdo->prepare("SELECT c.*, u.username FROM chat_messages c JOIN users u ON c.from_user_id = u.id WHERE c.thread_id = ? ORDER BY c.created_at ASC");
            $stmt->execute([$thread_id]);
            $messages = $stmt->fetchAll();
        } else {
            $messages = [];
        }
    } else {
        // Fallback to old logic if chat_threads doesn't exist
        $stmt = $pdo->query("SELECT c.*, u.username FROM chat_messages c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 50");
        $messages = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $messages = [];
    $error = "Error loading chat history: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .chat-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 8px;
            position: relative;
        }
        .message strong {
            display: block;
            font-size: 0.9em;
            color: #333;
        }
        .message small {
            position: absolute;
            bottom: 5px;
            right: 10px;
            font-size: 0.8em;
            color: #666;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: none;
            font-size: 1em;
        }
        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>

        <div class="chat-container">
            <div class="chat-header">
                <h2>Live Chat Support</h2>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="success-message"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <div class="chat-messages">
                <?php foreach ($messages as $message): ?>
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
                <button type="submit" class="btn-primary">Send Message</button>
            </form>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>