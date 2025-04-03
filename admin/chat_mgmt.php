<?php
/**
 * Chat Management
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Chat Management";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get active tab
$tab = $_GET['tab'] ?? 'inbox';

// Get online users
$onlineUsers = [];
try {
    $stmt = $pdo->query("SELECT u.id, u.username, u.avatar, cs.last_active 
                         FROM users u
                         JOIN chat_status cs ON u.id = cs.user_id
                         WHERE cs.is_online = 1 AND u.id != " . $_SESSION['user_id'] . "
                         ORDER BY cs.last_active DESC");
    $onlineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Online users error: " . $e->getMessage());
}

// Get inbox threads
$inboxThreads = [];
try {
    $stmt = $pdo->query("SELECT ct.*, u.username, u.avatar, 
                         (SELECT COUNT(*) FROM chat_messages cm 
                          WHERE cm.thread_id = ct.id AND cm.is_admin = 0 AND cm.is_read = 0) as unread_count
                         FROM chat_threads ct
                         JOIN users u ON ct.user_id = u.id
                         WHERE ct.status = 'open'
                         ORDER BY ct.updated_at DESC");
    $inboxThreads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Inbox threads error: " . $e->getMessage());
}

// Get active thread if specified
$activeThread = null;
if (isset($_GET['thread_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT ct.*, u.username, u.avatar 
                              FROM chat_threads ct
                              JOIN users u ON ct.user_id = u.id
                              WHERE ct.id = ?");
        $stmt->execute([$_GET['thread_id']]);
        $activeThread = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($activeThread) {
            // Mark messages as read
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 
                          WHERE thread_id = ? AND is_admin = 0")
               ->execute([$activeThread['id']]);
        }
    } catch (Exception $e) {
        error_log("Active thread error: " . $e->getMessage());
    }
}

// Handle admin message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['thread_id'])) {
    $message = filter_input(INPUT_POST, 'message', FILTER_UNSAFE_RAW);
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $thread_id = filter_input(INPUT_POST, 'thread_id', FILTER_VALIDATE_INT);

    if ($thread_id && $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO chat_messages (thread_id, user_id, message, is_admin) VALUES (?, ?, ?, 1)");
            $stmt->execute([$thread_id, $_SESSION['user_id'], $message]);
            $success = "Message sent successfully!";
        } catch (PDOException $e) {
            $error = "Error sending message: " . $e->getMessage();
        }
    } else {
        $error = "Invalid thread or message.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Manage live chats with users</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge"><?= countUnreadNotifications($pdo, $_SESSION['user_id']) ?></span>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications dropdown content -->
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="chat-container">
                    <!-- Left sidebar -->
                    <div class="chat-sidebar">
                        <div class="chat-tabs">
                            <button class="chat-tab <?= $tab === 'online' ? 'active' : '' ?>" data-tab="online">
                                <i class="fas fa-users"></i> Online Users (<?= count($onlineUsers) ?>)
                            </button>
                            <button class="chat-tab <?= $tab === 'inbox' ? 'active' : '' ?>" data-tab="inbox">
                                <i class="fas fa-inbox"></i> Inbox
                                <?php if (array_sum(array_column($inboxThreads, 'unread_count')) > 0): ?>
                                    <span class="unread-badge"><?= array_sum(array_column($inboxThreads, 'unread_count')) ?></span>
                                <?php endif; ?>
                            </button>
                        </div>
                        
                        <div class="chat-list-container">
                            <!-- Online Users Tab -->
                            <div class="chat-list <?= $tab === 'online' ? 'active' : '' ?>" id="online-list">
                                <?php if (!empty($onlineUsers)): ?>
                                    <?php foreach ($onlineUsers as $user): ?>
                                        <div class="chat-list-item online-user" data-user-id="<?= $user['id'] ?>">
                                            <div class="user-avatar">
                                                <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['username']) ?>">
                                                <span class="online-dot"></span>
                                            </div>
                                            <div class="user-info">
                                                <h4><?= htmlspecialchars($user['username']) ?></h4>
                                                <small>Active <?= timeAgo($user['last_active']) ?></small>
                                            </div>
                                            <button class="btn-start-chat" title="Start Chat">
                                                <i class="fas fa-comment-dots"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-items">
                                        <i class="fas fa-user-slash"></i>
                                        <p>No users online</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Inbox Tab -->
                            <div class="chat-list <?= $tab === 'inbox' ? 'active' : '' ?>" id="inbox-list">
                                <?php if (!empty($inboxThreads)): ?>
                                    <?php foreach ($inboxThreads as $thread): ?>
                                        <div class="chat-list-item chat-thread <?= $activeThread && $activeThread['id'] == $thread['id'] ? 'active' : '' ?> 
                                            <?= $thread['unread_count'] > 0 ? 'unread' : '' ?>" 
                                            data-thread-id="<?= $thread['id'] ?>">
                                            <div class="user-avatar">
                                                <img src="../uploads/avatars/<?= htmlspecialchars($thread['avatar']) ?>" alt="<?= htmlspecialchars($thread['username']) ?>">
                                                <?php if ($thread['unread_count'] > 0): ?>
                                                    <span class="unread-count"><?= $thread['unread_count'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="thread-info">
                                                <h4><?= htmlspecialchars($thread['username']) ?></h4>
                                                <p class="thread-subject"><?= htmlspecialchars($thread['subject']) ?></p>
                                                <small>Updated <?= timeAgo($thread['updated_at']) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-items">
                                        <i class="fas fa-inbox"></i>
                                        <p>No active conversations</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main chat area -->
                    <div class="chat-main">
                        <?php if ($activeThread): ?>
                            <div class="chat-header">
                                <div class="chat-user">
                                    <div class="user-avatar">
                                        <img src="../uploads/avatars/<?= htmlspecialchars($activeThread['avatar']) ?>" alt="<?= htmlspecialchars($activeThread['username']) ?>">
                                    </div>
                                    <div class="user-info">
                                        <h3><?= htmlspecialchars($activeThread['username']) ?></h3>
                                        <small>Subject: <?= htmlspecialchars($activeThread['subject']) ?></small>
                                    </div>
                                </div>
                                <div class="chat-actions">
                                    <button class="btn-chat-action" title="Add Notes" id="btn-add-notes">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-chat-action" title="Close Chat" id="btn-close-chat">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="chat-messages" id="chat-messages">
                                <!-- Messages will be loaded via AJAX -->
                                <div class="loading-messages">
                                    <i class="fas fa-spinner fa-spin"></i> Loading messages...
                                </div>
                            </div>
                            
                            <div class="chat-input">
                                <form method="POST">
                                    <input type="hidden" name="thread_id" value="<?= $activeThread['id'] ?? '' ?>">
                                    <div class="message-input">
                                        <textarea name="message" placeholder="Type your message here..." rows="1" required></textarea>
                                        <button type="submit" class="btn-send">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="no-chat-selected">
                                <i class="fas fa-comments"></i>
                                <h3>Select a conversation</h3>
                                <p>Choose an online user or an existing thread from the sidebar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notes Modal -->
    <div class="modal" id="notes-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Chat Notes</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="notes-form">
                    <input type="hidden" name="thread_id" value="<?= $activeThread['id'] ?? '' ?>">
                    <textarea name="admin_notes" placeholder="Add private notes about this conversation..."><?= $activeThread['admin_notes'] ?? '' ?></textarea>
                    <button type="submit" class="btn btn-primary">Save Notes</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Quick Responses Modal -->
    <div class="modal" id="quick-responses-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Responses</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="quick-responses-list">
                    <!-- Quick responses will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/chat.js"></script>
    <script>
const adminId = <?= $_SESSION['user_id'] ?>;
const ws = new WebSocket('ws://<?= $_SERVER['HTTP_HOST'] ?>:8080?user_id=' + adminId + '&role=admin');

// WebSocket handlers
ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    
    if (data.type === 'message') {
        if (activeThreadId === data.thread_id) {
            appendMessage(data);
        }
        updateUnreadCounts();
        updateThreadList();
    }
};

// Send message function
function sendAdminMessage(message, threadId, recipientId) {
    const msgData = {
        type: 'message',
        thread_id: threadId,
        user_id: adminId,
        message: message,
        is_admin: true,
        recipient_id: recipientId
    };
    ws.send(JSON.stringify(msgData));
}

// Update unread counts
function updateUnreadCounts() {
    fetch('chat_actions.php?action=get_unread_counts')
        .then(response => response.json())
        .then(data => {
            // Update UI with new counts
            if (data.total_unread > 0) {
                $('.unread-badge').text(data.total_unread).show();
            } else {
                $('.unread-badge').hide();
            }
        });
}

// Update thread list
function updateThreadList() {
    fetch('chat_actions.php?action=get_threads')
        .then(response => response.json())
        .then(data => {
            // Update thread list UI
        });
}

// Initialize for active thread
<?php if ($activeThread): ?>
    let activeThreadId = <?= $activeThread['id'] ?>;
    let recipientId = <?= $activeThread['user_id'] ?>;
    
    // Load initial messages
    loadMessages(activeThreadId);
    
    // Message submit handler
    $('.chat-input form').submit(function(e) {
        e.preventDefault();
        const message = $(this).find('textarea').val().trim();
        if (message) {
            sendAdminMessage(message, activeThreadId, recipientId);
            $(this).find('textarea').val('');
        }
    });
<?php endif; ?>

// Load messages function
function loadMessages(threadId) {
    fetch(`chat_actions.php?action=get_messages&thread_id=${threadId}`)
        .then(response => response.json())
        .then(data => {
            // Update messages UI
        });
}

// Append new message
function appendMessage(data) {
    const messageHtml = `
        <div class="message ${data.is_admin ? 'admin' : 'user'}">
            <div class="message-header">
                <span class="username">${data.is_admin ? 'You' : data.username}</span>
                <span class="time">${new Date().toLocaleTimeString()}</span>
            </div>
            <div class="message-content">${data.message}</div>
        </div>
    `;
    $('#chat-messages').append(messageHtml);
    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
}

// Update online users every 10 seconds
setInterval(() => {
    fetch('chat_actions.php?action=get_online_users')
        .then(response => response.json())
        .then(data => {
            // Update online users UI
        });
}, 10000);
</script>
</body>
</html>

<?php include 'includes/footer.php'; ?>