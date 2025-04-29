<?php
/**
 * 
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 * 
 * User Messaging Page - Allows users to chat with online admins.
 * Features:
 * - Shows online admins with unread message counts.
 * - Allows users to start chat, delete messages, clear chat.
 * - Fully responsive, ERPNext-inspired, adugna-patented styles.
 * - User guidance and interactive UI.
 */
ob_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireLogin();

$pageTitle = "Messaging";

// Get all online admins (role = 'admin' and online = 1), exclude current user
$currentUserId = $_SESSION['user_id'];
$usersStmt = $pdo->prepare("SELECT id, username FROM users WHERE role_id = 1 AND online = 1 AND id != ? ORDER BY username");
$usersStmt->execute([$currentUserId]);
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread counts for each admin
$unreadCounts = [];
$stmt = $pdo->prepare("SELECT sender_id, COUNT(*) as unread FROM messages WHERE is_read = 0 AND receiver_id = ? GROUP BY sender_id");
$stmt->execute([$_SESSION['user_id']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $unreadCounts[$row['sender_id']] = $row['unread'];
}

// Get selected user from query string (for direct chat from inbox)
$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Handle delete message request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $messageId = intval($_POST['delete_message_id']);
    $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id AND (sender_id = :user_id OR receiver_id = :user_id)');
    $stmt->execute([':id' => $messageId, ':user_id' => $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Handle clear chat request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_chat_with'])) {
    $chatWith = intval($_POST['clear_chat_with']);
    $stmt = $pdo->prepare('DELETE FROM messages WHERE (sender_id = :user_id AND receiver_id = :chat_with) OR (sender_id = :chat_with AND receiver_id = :user_id)');
    $stmt->execute([':user_id' => $_SESSION['user_id'], ':chat_with' => $chatWith]);
    echo json_encode(['success' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Users Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Main and custom styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/messages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Adugna patenting styles (adugna- prefix, ERPNext-inspired, compact, responsive) */
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
        }
        .adugna-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.2rem 1rem;
            margin-bottom: 1.2rem;
        }
        .adugna-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.28rem 0.8rem;
            font-size: 0.93rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
        }
        .adugna-btn:hover {
            background: #1741a6;
        }
        .adugna-btn-secondary {
            background: #f5f7fa;
            color: #2563eb;
            border: 1px solid #d1d8dd;
        }
        .adugna-btn-secondary:hover {
            background: #e4e8ec;
        }
        .adugna-input, .adugna-textarea {
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 7px 10px;
            font-size: 0.97rem;
            background: #f5f7fa;
            color: #36414c;
        }
        .adugna-input:focus, .adugna-textarea:focus {
            outline: none;
            border-color: #2563eb;
            background: #fff;
        }
        .adugna-label {
            font-weight: 500;
            color: #2563eb;
            margin-bottom: 3px;
            display: block;
            font-size: 0.97rem;
        }
        .adugna-user-list {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 420px;
            overflow-y: auto;
        }
        .adugna-user-list li.adugna-contact-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.15s;
            position: relative;
            border-radius: 6px;
        }
        .adugna-user-list li.adugna-contact-item.selected,
        .adugna-user-list li.adugna-contact-item:hover {
            background: #eaf3fb;
        }
        .adugna-user-list .adugna-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e3eafc;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            margin-right: 10px;
            border: 2px solid #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .adugna-online-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #44d600;
            display: inline-block;
            margin-right: 6px;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #eaf3fb;
        }
        .adugna-unread-badge {
            background: #ff5252;
            color: #fff;
            border-radius: 10px;
            font-size: 0.82rem;
            padding: 1px 7px;
            margin-left: auto;
            font-weight: 600;
        }
        .adugna-chat-messages {
            max-height: 340px;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid #d1d8dd;
            border-radius: 5px;
            background: #f9f9f9;
            margin-bottom: 10px;
            font-size: 0.97rem;
        }
        .adugna-chat-messages::-webkit-scrollbar {
            width: 7px;
        }
        .adugna-chat-messages::-webkit-scrollbar-thumb {
            background: #d1d8dd;
            border-radius: 4px;
        }
        .adugna-chat-messages::-webkit-scrollbar-thumb:hover {
            background: #b0b8c1;
        }
        .adugna-message-form textarea {
            width: 100%;
            resize: vertical;
            min-height: 48px;
            max-height: 140px;
        }
        .adugna-direction {
            background: #eaf3fb;
            color: #2563eb;
            border: 1px solid #b7e4c7;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 1.1em;
            font-size: 0.98em;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        @media (max-width: 900px) {
            .messaging-container {
                flex-direction: column;
            }
            .contact-list, .chat-section {
                width: 100% !important;
                max-width: 100% !important;
            }
            .adugna-chat-messages {
                max-height: 220px;
            }
        }
        @media (max-width: 600px) {
            .adugna-card {
                padding: 0.7rem 0.3rem;
            }
            .adugna-btn, .adugna-btn-secondary {
                font-size: 0.89rem;
                padding: 0.18rem 0.6rem;
            }
            .adugna-label {
                font-size: 0.93rem;
            }
            .adugna-chat-messages {
                font-size: 0.93rem;
            }
        }
        .adugna-contact-tooltip {
            display: none;
            position: absolute;
            left: 110%;
            top: 50%;
            transform: translateY(-50%);
            background: #2563eb;
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.92em;
            white-space: nowrap;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(44,62,80,0.09);
        }
        .adugna-contact-item:hover .adugna-contact-tooltip,
        .adugna-contact-item:focus .adugna-contact-tooltip {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="container">
            <!-- User Guidance Card -->
            <div class="adugna-card adugna-direction">
                <i class="fa fa-info-circle"></i>
                <span>
                    <strong>How to chat with an admin:</strong>
                    <ul style="margin:0.5em 0 0 1.2em;padding:0;">
                        <li>You can Click on one of an online admin to chat with".</li>
                        <li>Unread message counts are shown in red badges.</li>
                    </ul>
                </span>
            </div>
            <header style="margin-bottom: 14px;">
                <h1 style="font-size: 1.3rem; font-weight: 700; margin: 0; color:#2563eb;">
                    <i class="fas fa-comments"></i> <?= htmlspecialchars($pageTitle) ?>
                </h1>
            </header>
            <div class="messaging-container" style="display:flex;gap:1.5em;flex-wrap:wrap;">
                <!-- Contact List (Admins) -->
                <aside class="contact-list" style="width:260px;min-width:200px;">
                    <h2 style="font-size: 1.05rem; font-weight: 600; margin: 12px 0 8px 8px; color:#2563eb;">
                        <i class="fas fa-users"></i> Online Admins
                    </h2>
                    <ul id="user-list" class="adugna-user-list">
                        <?php foreach ($users as $user): 
                            $initials = strtoupper(substr($user['username'], 0, 2));
                            $isSelected = ($selectedUserId && $selectedUserId == $user['id']);
                        ?>
                            <li data-user-id="<?= $user['id'] ?>" class="adugna-contact-item<?= $isSelected ? ' selected' : '' ?>" tabindex="0" style="position:relative;">
                                <span class="adugna-avatar"><?= htmlspecialchars($initials) ?></span>
                                <span>
                                    <span class="adugna-online-dot"></span>
                                    <?= htmlspecialchars($user['username']) ?>
                                </span>
                                <?php if (isset($unreadCounts[$user['id']])): ?>
                                    <span class="adugna-unread-badge"><?= $unreadCounts[$user['id']] ?></span>
                                <?php endif; ?>
                                <!-- Hover tooltip for chat (not a button, just a hover text) -->
                                <span class="adugna-contact-tooltip">
                                    <i class="fa fa-hand-pointer"></i> Click to chat with me
                                </span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <li style="color:#888;">No admins are online.</li>
                        <?php endif; ?>
                    </ul>
                </aside>
                <!-- Chat Section -->
                <section class="chat-section" style="flex:1;min-width:270px;max-width:100%;">
                    <div id="chat-header" class="chat-header">
                        <h3 style="margin:0; font-size:1.05rem; color:#333;">
                            <i class="fas fa-comment-dots"></i> Select an online admin to start chatting
                        </h3>
                    </div>
                    <div id="chat-messages" class="adugna-chat-messages"></div>
                    <form id="message-form" class="adugna-message-form" style="display:none;">
                        <input type="hidden" name="receiver_id" id="receiver_id">
                        <label class="adugna-label" for="message-input">Message</label>
                        <textarea name="message" id="message-input" rows="3" placeholder="Type your message..." required class="adugna-textarea"></textarea>
                        <button type="submit" class="adugna-btn" style="margin-top:8px;">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </form>
                    <div style="margin-top: 10px;">
                        <button id="clear-chat" class="adugna-btn adugna-btn-secondary" style="display:none;">
                            <i class="fas fa-trash-alt"></i> Clear Chat
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    <script>
        // Developer: Adugna Gizaw | User Messaging JS
        // Pass PHP variables to JS for chat logic
        window.messagesConfig = {
            selectedUserId: <?= $selectedUserId ? json_encode($selectedUserId) : 'null' ?>,
            currentUser: <?= $_SESSION['user_id'] ?? 0 ?>
        };

        // Highlight selected admin and show tooltip on hover
        document.addEventListener('DOMContentLoaded', function() {
            const userList = document.getElementById('user-list');
            if (userList) {
                userList.addEventListener('click', function(e) {
                    let li = e.target.closest('li.adugna-contact-item');
                    if (li) {
                        userList.querySelectorAll('li.adugna-contact-item').forEach(el => el.classList.remove('selected'));
                        li.classList.add('selected');
                        // Optionally, trigger chat load here
                    }
                });
                // Accessibility: allow keyboard navigation
                userList.querySelectorAll('li.adugna-contact-item').forEach(function(li) {
                    li.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            li.click();
                        }
                    });
                });
            }

            // Show "Click to chat with me" tooltip on hover for online admins
            document.querySelectorAll('.adugna-contact-item').forEach(function(item) {
                item.addEventListener('mouseenter', function() {
                    var tooltip = item.querySelector('.adugna-contact-tooltip');
                    if (tooltip) tooltip.style.display = 'block';
                });
                item.addEventListener('mouseleave', function() {
                    var tooltip = item.querySelector('.adugna-contact-tooltip');
                    if (tooltip) tooltip.style.display = 'none';
                });
                // For accessibility: show tooltip on focus, hide on blur
                item.addEventListener('focus', function() {
                    var tooltip = item.querySelector('.adugna-contact-tooltip');
                    if (tooltip) tooltip.style.display = 'block';
                });
                item.addEventListener('blur', function() {
                    var tooltip = item.querySelector('.adugna-contact-tooltip');
                    if (tooltip) tooltip.style.display = 'none';
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const clearChatButton = document.getElementById('clear-chat');
            const chatMessages = document.getElementById('chat-messages');

            // Handle clear chat
            clearChatButton.addEventListener('click', function () {
                const receiverId = document.getElementById('receiver_id').value;
                if (receiverId && confirm('Are you sure you want to clear this chat?')) {
                    fetch('messages.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ clear_chat_with: receiverId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            chatMessages.innerHTML = '';
                            alert('Chat cleared successfully.');
                        }
                    });
                }
            });

            // Handle delete individual message
            chatMessages.addEventListener('click', function (e) {
                if (e.target.classList.contains('delete-message')) {
                    const messageId = e.target.dataset.messageId;
                    if (messageId && confirm('Are you sure you want to delete this message?')) {
                        fetch('messages.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ delete_message_id: messageId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                e.target.closest('.message-item').remove();
                                alert('Message deleted successfully.');
                            }
                        });
                    }
                }
            });
        });
    </script>
    <script src="../assets/js/messages.js"></script>
    <script src="../includes/activity-tracker.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>