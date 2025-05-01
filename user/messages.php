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
        /**
         * Adugna patented UI styles - compact, responsive, and branded.
         * All custom classes use adugna- prefix for branding and patenting.
         * Designed for outstanding, flexible, and interactive user experience.
         */

        /* Base font and sizing for all elements */
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
            color: #23272f;
        }

        /* Adugna card: compact, rounded, shadowed container */
        .adugna-card {
            background: #fff;
            border-radius: 9px;
            box-shadow: 0 1.5px 7px rgba(44,62,80,0.08);
            padding: 0.85rem 0.7rem;
            margin-bottom: 1rem;
            transition: box-shadow 0.18s;
        }

        /* Adugna button: compact, branded, interactive */
        .adugna-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 3.5px;
            padding: 0.18rem 0.65rem;
            font-size: 0.92rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.16s, box-shadow 0.16s;
            box-shadow: 0 1px 3px rgba(44,62,80,0.07);
            min-height: 25px;
            display: inline-flex;
            align-items: center;
            gap: 0.22em;
            line-height: 1.1;
        }
        .adugna-btn i {
            font-size: 0.93em;
            margin-right: 2px;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: #1741a6;
        }
        .adugna-btn-secondary {
            background: #f5f7fa;
            color: #2563eb;
            border: 1px solid #d1d8dd;
        }
        .adugna-btn-secondary:hover, .adugna-btn-secondary:focus {
            background: #e4e8ec;
        }

        /* Adugna input and textarea: compact, clean, branded */
        .adugna-input, .adugna-textarea {
            border: 1px solid #d1d8dd;
            border-radius: 3.5px;
            padding: 6px 9px;
            font-size: 0.96rem;
            background: #f5f7fa;
            color: #36414c;
            transition: border-color 0.15s, background 0.15s;
        }
        .adugna-input:focus, .adugna-textarea:focus {
            outline: none;
            border-color: #2563eb;
            background: #fff;
        }

        /* Adugna label: compact, branded */
        .adugna-label {
            font-weight: 500;
            color: #2563eb;
            margin-bottom: 4px; /* Increased for better spacing above textarea */
            display: block;
            font-size: 0.96rem;
        }

        /* Adugna user list: compact, scrollable */
        .adugna-user-list {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 370px;
            overflow-y: auto;
        }
        .adugna-user-list li.adugna-contact-item {
            display: flex;
            align-items: center;
            padding: 6px 9px;
            cursor: pointer;
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.13s;
            position: relative;
            border-radius: 5px;
            min-height: 34px;
        }
        .adugna-user-list li.adugna-contact-item.selected,
        .adugna-user-list li.adugna-contact-item:hover {
            background: #eaf3fb;
        }
        .adugna-user-list .adugna-avatar {
            width: 23px;
            height: 23px;
            border-radius: 50%;
            background: #e3eafc;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.93rem;
            margin-right: 7px;
            border: 2px solid #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .adugna-online-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #44d600;
            display: inline-block;
            margin-right: 4px;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #eaf3fb;
        }
        .adugna-unread-badge {
            background: #ff5252;
            color: #fff;
            border-radius: 8px;
            font-size: 0.78rem;
            padding: 0px 5px;
            margin-left: auto;
            font-weight: 600;
            min-width: 16px;
            min-height: 14px;
            line-height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Adugna chat messages: compact, scrollable, branded */
        .adugna-chat-messages {
            max-height: 260px;
            overflow-y: auto;
            padding: 6px;
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            background: #f9f9f9;
            margin-bottom: 7px;
            font-size: 0.95rem;
        }
        .adugna-chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        .adugna-chat-messages::-webkit-scrollbar-thumb {
            background: #d1d8dd;
            border-radius: 3px;
        }
        .adugna-chat-messages::-webkit-scrollbar-thumb:hover {
            background: #b0b8c1;
        }

        /* Adugna message form: compact and aligned */
        .adugna-message-form {
            display: flex;
            flex-direction: column;
            gap: 0.3em;
        }
        .adugna-message-form textarea.adugna-textarea {
            width: 100%;
            resize: vertical;
            min-height: 38px;
            max-height: 100px;
            margin-bottom: 0.3em; /* Space below textarea before button */
            box-sizing: border-box;
            font-size: 0.96rem;
            border-radius: 3.5px;
            border: 1px solid #d1d8dd;
            padding: 7px 10px;
            background: #f5f7fa;
            color: #36414c;
            transition: border-color 0.15s, background 0.15s;
        }
        .adugna-message-form textarea.adugna-textarea:focus {
            outline: none;
            border-color: #2563eb;
            background: #fff;
        }

        /* Adugna direction/info card: compact, branded */
        .adugna-direction {
            background: #eaf3fb;
            color: #2563eb;
            border: 1px solid #b7e4c7;
            border-radius: 5px;
            padding: 7px 10px;
            margin-bottom: 0.8em;
            font-size: 0.96em;
            display: flex;
            align-items: center;
            gap: 0.4em;
        }
        .adugna-direction i {
            font-size: 1em;
            margin-right: 4px;
        }

        /* Adugna contact tooltip: only on hover/focus, not a button */
        .adugna-contact-tooltip {
            display: none;
            position: absolute;
            left: 108%;
            top: 50%;
            transform: translateY(-50%);
            background: #2563eb;
            color: #fff;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 0.89em;
            white-space: nowrap;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(44,62,80,0.09);
            pointer-events: none;
        }
        .adugna-contact-item:hover .adugna-contact-tooltip,
        .adugna-contact-item:focus .adugna-contact-tooltip {
            display: block;
        }

        /* Adugna chat section header: compact */
        .adugna-chat-header {
            margin-bottom: 7px;
        }
        .adugna-chat-header h3 {
            font-size: 1.01rem;
            color: #333;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.3em;
        }
        .adugna-chat-header i {
            font-size: 1em;
            margin-right: 3px;
        }

        /* Adugna responsive layout: outstanding and flexible */
        .adugna-messaging-container {
            display: flex;
            gap: 1em;
            flex-wrap: wrap;
            align-items: flex-start;
        }
        .adugna-contact-list {
            width: 210px;
            min-width: 150px;
            max-width: 260px;
            flex-shrink: 0;
        }
        .adugna-chat-section {
            flex: 1;
            min-width: 200px;
            max-width: 100%;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 900px) {
            .adugna-messaging-container {
                flex-direction: column;
                gap: 0.7em;
            }
            .adugna-contact-list, .adugna-chat-section {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
            .adugna-chat-messages {
                max-height: 160px;
            }
        }
        @media (max-width: 600px) {
            .adugna-card {
                padding: 0.5rem 0.18rem;
            }
            .adugna-btn, .adugna-btn-secondary {
                font-size: 0.85rem;
                padding: 0.12rem 0.45rem;
                min-height: 21px;
            }
            .adugna-label {
                font-size: 0.91rem;
            }
            .adugna-chat-messages {
                font-size: 0.91rem;
            }
            .adugna-user-list .adugna-avatar {
                width: 18px;
                height: 18px;
                font-size: 0.8rem;
            }
            .adugna-unread-badge {
                font-size: 0.7rem;
                min-width: 12px;
                min-height: 10px;
            }
        }
        @media (max-width: 400px) {
            body, input, textarea, select, button {
                font-size: 13px;
            }
            .adugna-card {
                padding: 0.3rem 0.08rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="container">
            <!-- Adugna direction/info card for user guidance -->
            <div class="adugna-card adugna-direction">
                <i class="fa fa-info-circle"></i>
                <span></span></span>
                    <strong>How to chat with an admin:</strong>
                    <ul style="margin:0.4em 0 0 1.1em;padding:0;">
                        <li>Click on an online admin to chat.</li>
                        <li>Unread message counts are shown in red badges.</li>
                    </ul>
                </span>
            </div>
            <!-- Adugna page header -->
            <header style="margin-bottom: 10px;">
                <h1 style="font-size: 1.13rem; font-weight: 700; margin: 0; color:#2563eb; display:flex;align-items:center;gap:0.3em;">
                    <i class="fas fa-comments" style="font-size:1em;"></i> <?= htmlspecialchars($pageTitle) ?>
                </h1>
            </header>
            <div class="adugna-messaging-container">
                <!-- Adugna contact list (Admins) -->
                <aside class="adugna-contact-list">
                    <h2 style="font-size: 0.99rem; font-weight: 600; margin: 10px 0 6px 7px; color:#2563eb;display:flex;align-items:center;gap:0.3em;">
                        <i class="fas fa-users" style="font-size:0.93em;"></i> Online Admins
                    </h2>
                    <ul id="user-list" class="adugna-user-list">
                        <?php foreach ($users as $user): 
                            $initials = strtoupper(substr($user['username'], 0, 2));
                            $isSelected = ($selectedUserId && $selectedUserId == $user['id']);
                        ?>
                            <li data-user-id="<?= $user['id'] ?>" class="adugna-contact-item<?= $isSelected ? ' selected' : '' ?>" tabindex="0" style="position:relative;">
                                <span class="adugna-avatar"></span>
                                    <?= htmlspecialchars($initials) ?>
                                </span>
                                <span style="display:flex;align-items:center;gap:3px;">
                                    <span class="adugna-online-dot"></span>
                                    <span style="font-size:0.95em;"><?= htmlspecialchars($user['username']) ?></span>
                                </span>
                                <?php if (isset($unreadCounts[$user['id']])): ?>
                                    <span class="adugna-unread-badge">
                                        <?= $unreadCounts[$user['id']] ?>
                                    </span>
                                <?php endif; ?>
                                <!-- Adugna tooltip: only on hover/focus, not a button -->
                                <span class="adugna-contact-tooltip">
                                    <i class="fa fa-hand-pointer"></i> Chat
                                </span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <li style="color:#888;font-size:0.92em;">No admins are online.</li>
                        <?php endif; ?>
                    </ul>
                </aside>
                <!-- Adugna chat section -->
                <section class="adugna-chat-section">
                    <div id="chat-header" class="adugna-chat-header">
                        <h3>
                            <i class="fas fa-comment-dots"></i> Select an online admin to start chatting
                        </h3>
                    </div>
                    <div id="chat-messages" class="adugna-chat-messages"></div>
                    <form id="message-form" class="adugna-message-form" style="display:none;">
                        <input type="hidden" name="receiver_id" id="receiver_id">
                        <label class="adugna-label" for="message-input">Message</label>
                        <textarea name="message" id="message-input" rows="3" placeholder="Type your message..." required class="adugna-textarea"></textarea>
                        <button type="submit" class="adugna-btn" style="margin-top:2px;">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </form>
                    <div style="margin-top: 7px;">
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
        /**
         * Developer: Adugna Gizaw
         * Adugna Messaging JS - handles UI interactivity and chat logic.
         */
        // Pass PHP variables to JS for chat logic
        window.messagesConfig = {
            selectedUserId: <?= $selectedUserId ? json_encode($selectedUserId) : 'null' ?>,
            currentUser: <?= $_SESSION['user_id'] ?? 0 ?>
        };

        // Adugna: Highlight selected admin and show tooltip on hover/focus
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
                // Adugna: Accessibility - allow keyboard navigation
                userList.querySelectorAll('li.adugna-contact-item').forEach(function(li) {
                    li.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            li.click();
                        }
                    });
                });
            }

            // Adugna: Tooltip only on hover/focus for online admins
            document.querySelectorAll('.adugna-contact-item').forEach(function(item) {
                item.addEventListener('mouseenter', function() {
                    var tooltip = item.querySelector('.adugna-contact-tooltip');
                    if (tooltip) tooltip.style.display = 'block';
                });
                item.addEventListener('mouseleave', function() {
                    var tooltip = item.querySelector('.adugna-contact-tooltip');
                    if (tooltip) tooltip.style.display = 'none';
                });
                // Accessibility: show tooltip on focus, hide on blur
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

            // Adugna: Handle clear chat
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

            // Adugna: Handle delete individual message
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