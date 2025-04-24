<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
ob_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireLogin();

$pageTitle = "Messaging";

// Get all online admins (role_id = 0 and online = 1)
$currentUserId = $_SESSION['user_id'];
$users = $pdo->prepare("SELECT id, username FROM users WHERE role_id = 0 AND online = 1 ORDER BY username");
$users->execute();
$users = $users->fetchAll(PDO::FETCH_ASSOC);

// Get unread counts for each admin
$unreadCounts = [];
$stmt = $pdo->prepare("SELECT sender_id, COUNT(*) as unread FROM messages WHERE is_read = 0 AND receiver_id = ? GROUP BY sender_id");
$stmt->execute([$_SESSION['user_id']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $unreadCounts[$row['sender_id']] = $row['unread'];
}

// Get selected user from query string (for direct chat from inbox)
$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Users Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/messages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
        }
        .erpnext-btn {
            background: #f5f7fa;
            color: #36414c;
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }
        .erpnext-btn.btn-primary {
            background: #007bfc;
            color: #fff;
            border-color: #007bfc;
        }
        .erpnext-btn.btn-primary:hover {
            background: #0056b3;
            color: #fff;
        }
        .erpnext-btn.btn-secondary {
            background: #f5f7fa;
            color: #36414c;
            border-color: #d1d8dd;
        }
        .erpnext-btn.btn-secondary:hover {
            background: #e4e8ec;
        }
        .erpnext-input, .erpnext-textarea {
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
            background: #f5f7fa;
            color: #36414c;
        }
        .erpnext-input:focus, .erpnext-textarea:focus {
            outline: none;
            border-color: #007bfc;
            background: #fff;
        }
        .erpnext-label {
            font-weight: 500;
            color: #36414c;
            margin-bottom: 4px;
            display: block;
        }
        .user-list li.selected {
            background-color: #eaf3fb;
            font-weight: 600;
        }
        /* ...keep .online-dot, .unread-badge, etc. from messages.css... */
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="container">
            <header style="margin-bottom: 18px;">
                <h1 style="font-size: 2rem; font-weight: 700; margin: 0; color:#007bff;">
                    <i class="fas fa-comments"></i> <?= htmlspecialchars($pageTitle) ?>
                </h1>
            </header>
            <div class="messaging-container">
                <aside class="contact-list">
                    <h2 style="font-size: 1.2rem; font-weight: 600; margin: 18px 0 10px 18px; color:#007bff;">
                        <i class="fas fa-users"></i> Online Admins
                    </h2>
                    <ul id="user-list" class="user-list">
                        <?php foreach ($users as $user): ?>
                            <li data-user-id="<?= $user['id'] ?>" class="contact-item">
                                <span>
                                    <span class="online-dot"></span>
                                    <?= htmlspecialchars($user['username']) ?>
                                </span>
                                <?php if (isset($unreadCounts[$user['id']])): ?>
                                    <span class="unread-badge"><?= $unreadCounts[$user['id']] ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <li style="color:#888;">No admins are online.</li>
                        <?php endif; ?>
                    </ul>
                </aside>
                <section class="chat-section">
                    <div id="chat-header" class="chat-header">
                        <h3 style="margin:0; font-size:1.1rem; color:#333;">
                            <i class="fas fa-comment-dots"></i> Select an online admin to start chatting
                        </h3>
                    </div>
                    <div id="chat-messages" class="chat-messages"></div>
                    <form id="message-form" class="message-form" style="display:none;">
                        <input type="hidden" name="receiver_id" id="receiver_id">
                        <label class="erpnext-label" for="message-input">Message</label>
                        <textarea name="message" id="message-input" rows="3" placeholder="Type your message..." required class="erpnext-textarea" style="width:100%;resize:vertical;"></textarea>
                        <button type="submit" class="erpnext-btn btn-primary" style="margin-top:8px;">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    <script>
        // Pass PHP variables to JS
        window.messagesConfig = {
            selectedUserId: <?= $selectedUserId ? json_encode($selectedUserId) : 'null' ?>,
            currentUser: <?= $_SESSION['user_id'] ?? 0 ?>
        };
    </script>
    <script src="../assets/js/messages.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>