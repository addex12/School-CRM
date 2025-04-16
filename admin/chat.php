<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/db.php';

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['username'];

?>
<?php include 'includes/admin_sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat - School CRM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/admin_chat.js" defer></script>
</head>
<body>
    <div class="chat-container">
        <div class="user-list">
            <h3>Online Users</h3>
            <ul id="onlineUsers"></ul>
        </div>
        <div class="chat-box">
            <div id="chatMessages" class="messages"></div>
            <form id="chatForm">
                <input type="text" id="chatInput" placeholder="Type a message..." required>
                <button type="submit">Send</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php include 'includes/footer.php'; ?>