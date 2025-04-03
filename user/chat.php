<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

// Get or create chat thread
$stmt = $pdo->prepare("SELECT id FROM chat_threads WHERE user_id = ? AND status = 'open' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$thread = $stmt->fetch();

if (!$thread) {
    $pdo->prepare("INSERT INTO chat_threads (user_id, subject, status) VALUES (?, 'Support Request', 'open')")
       ->execute([$_SESSION['user_id']]);
    $thread_id = $pdo->lastInsertId();
} else {
    $thread_id = $thread['id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - Survey System</title>
    <link rel="stylesheet" href="../assets/css/chat.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>Live Support Chat</h2>
            <div class="chat-status" id="connection-status">Connecting...</div>
        </div>
        
        <div class="chat-messages" id="chat-messages">
            <?php
            $stmt = $pdo->prepare("SELECT cm.*, u.username 
                                  FROM chat_messages cm
                                  JOIN users u ON cm.user_id = u.id
                                  WHERE thread_id = ?
                                  ORDER BY created_at ASC");
            $stmt->execute([$thread_id]);
            while ($message = $stmt->fetch()):
            ?>
            <div class="message <?= $message['is_admin'] ? 'admin' : 'user' ?>">
                <div class="message-header">
                    <span class="username"><?= htmlspecialchars($message['username']) ?></span>
                    <span class="time"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                </div>
                <div class="message-content"><?= htmlspecialchars($message['message']) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="chat-input">
            <textarea id="message-input" placeholder="Type your message..."></textarea>
            <button id="send-button">Send</button>
        </div>
    </div>

    <script>
        const userId = <?= $_SESSION['user_id'] ?>;
        const threadId = <?= $thread_id ?>;
        const ws = new WebSocket('ws://your-domain:8080?user_id=' + userId);

        ws.onopen = () => {
            $('#connection-status').text('Online').addClass('connected');
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.thread_id === threadId) {
                appendMessage(data);
            }
        };

        $('#send-button').click(() => {
            const message = $('#message-input').val().trim();
            if (message) {
                const msgData = {
                    type: 'message',
                    thread_id: threadId,
                    user_id: userId,
                    message: message,
                    is_admin: false,
                    recipient_id: 'admin'
                };
                
                ws.send(JSON.stringify(msgData));
                $('#message-input').val('');
            }
        });

        function appendMessage(data) {
            const messageHtml = `
                <div class="message ${data.is_admin ? 'admin' : 'user'}">
                    <div class="message-header">
                        <span class="username">${data.is_admin ? 'Support Agent' : 'You'}</span>
                        <span class="time">${new Date().toLocaleTimeString()}</span>
                    </div>
                    <div class="message-content">${data.message}</div>
                </div>
            `;
            $('#chat-messages').append(messageHtml);
            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
        }
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>