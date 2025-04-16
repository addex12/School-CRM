<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/config.php';
$pageTitle = "Messages";
// Get all admins
$admins = $pdo->query("SELECT id, username FROM users WHERE role_id = 1 ORDER BY username")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - User Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <script src="../assets/js/messages.js" defer></script>
</head>
<body>
    <div class="user-dashboard">
        <?php include 'includes/header.php'; ?>
        <div class="user-main">
            <header class="user-header"><h1><?= htmlspecialchars($pageTitle) ?></h1></header>
            <div class="content">
                <div class="messaging-container">
                    <aside class="contact-list">
                        <h2>Admins</h2>
                        <ul id="user-list">
                            <?php foreach ($admins as $admin): ?>
                                <li data-user-id="<?= $admin['id'] ?>"> <?= htmlspecialchars($admin['username']) ?> </li>
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
