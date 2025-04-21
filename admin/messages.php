<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once '../includes/auth.php';
    requireAdmin();
    require_once '../includes/db.php';
    
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'send_message') {
            $receiver_id = $_POST['receiver_id'] === 'broadcast' ? null : (int)$_POST['receiver_id'];
            $message = trim($_POST['message']);
            
            if (empty($message)) {
                throw new Exception("Message cannot be empty");
            }
            
            $stmt = $pdo->prepare("INSERT INTO messages 
                                 (sender_id, receiver_id, subject, content, sent_at) 
                                 VALUES (?, ?, 'Admin Message', ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
            
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/messages.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header"><h1><?= htmlspecialchars($pageTitle) ?></h1></header>
            <div class="content">
                <div class="messaging-container">
                    <aside class="contact-list">
                        <h2>Users</h2>
                        <ul id="user-list">
                            <li data-user-id="broadcast" style="font-weight:bold;color:#007bff;">Broadcast to All Users</li>
                            <?php foreach ($users as $user): ?>
                                <li data-user-id="<?= $user['id'] ?>"> <?= htmlspecialchars($user['username']) ?> </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                    <section class="chat-section">
                        <div id="chat-header"></div>
                        <div id="chat-messages" class="chat-messages"></div>
                        <form id="message-form" class="message-form" style="display:none;">
                            <input type="hidden" name="receiver_id" id="receiver_id">
                            <textarea name="message" id="message-input" rows="2" placeholder="Type your message..."></textarea>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
