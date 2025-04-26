<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$pageTitle = "Admin Messaging";

// Get all non-admin users
$users = $pdo->query("SELECT id, username, last_active FROM users WHERE role_id != 1 AND active = 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get all admins (including self)
$admins = $pdo->query("SELECT id, username, last_active FROM users WHERE role_id = 1  ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get unread counts for each user (highlight users with unread messages)
$unreadCounts = [];
$stmt = $pdo->query("SELECT sender_id, COUNT(*) as unread FROM messages WHERE is_read = 0 AND receiver_id = {$_SESSION['user_id']} GROUP BY sender_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $unreadCounts[$row['sender_id']] = $row['unread'];
}

// Simulate online users (last_active within 5 minutes)
$now = time();
$onlineUsers = [];
foreach ($users as $u) {
    if (!empty($u['last_active']) && strtotime($u['last_active']) > $now - 300) {
        $onlineUsers[] = $u['id'];
    }
}
// Simulate online admins
$onlineAdmins = [];
foreach ($admins as $a) {
    if (!empty($a['last_active']) && strtotime($a['last_active']) > $now - 300) {
        $onlineAdmins[] = $a['id'];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
        }
        .admin-main {
            margin-left: 260px;
            padding: 2rem 2.5rem;
        }
        .messaging-container {
            display: flex;
            flex-direction: column;
            height: 80vh;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.07);
        }
        .contact-list {
            width: 100%;
            max-height: 150px;
            overflow-y: auto;
            background: #f8f9fa;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem;
        }
        .contact-list-header {
            font-size: 1.1rem;
            font-weight: 700;
            color: #215967;
            margin-bottom: 0.5rem;
        }
        .user-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .user-list li {
            padding: 8px 12px;
            background: #f5f7fa;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .user-list li:hover {
            background: #e2efda;
        }
        .user-list li.selected {
            background: #dbeafe;
        }
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #eaeff7;
        }
        .chat-header {
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #215967;
            border-bottom: 1px solid #e5e7eb;
            background: #f5f7fa;
        }
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #eaeff7;
        }
        .message-form {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            background: #fff;
            display: flex;
            gap: 1rem;
        }
        .message-form textarea {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
            resize: none;
        }
        .message-form .btn {
            padding: 10px 22px;
            font-size: 1rem;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .message-form .btn:hover {
            background: #0056b3;
        }
        .chat-message {
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 80%;
            word-break: break-word;
            font-size: 0.95rem;
            box-shadow: 0 1px 2px rgba(44, 62, 80, 0.07);
        }
        .chat-message.own {
            background: #d1f7c4;
            margin-left: auto;
            color: #215967;
        }
        .chat-message.other {
            background: #fff;
            margin-right: auto;
            color: #222d32;
        }
        @media (max-width: 768px) {
            .messaging-container {
                height: auto;
            }
            .contact-list {
                max-height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-envelope"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="messaging-container">
                    <aside class="contact-list">
                        <div class="contact-list-header">Users</div>
                        <ul class="user-list">
                            <li data-user-id="broadcast">Broadcast to All Users</li>
                            <?php foreach ($users as $user): ?>
                                <li data-user-id="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                    <section class="chat-section">
                        <div class="chat-header">Select a user to start chatting</div>
                        <div class="chat-messages"></div>
                        <form class="message-form">
                            <textarea placeholder="Type your message..."></textarea>
                            <button type="submit" class="btn"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
