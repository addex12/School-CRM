<?php
/**
 * View Support Ticket - Working Version
 */
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get ticket ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "Invalid ticket ID";
    header("Location: tickets.php");
    exit();
}

$ticketId = (int)$_GET['id'];

// Fetch ticket
$stmt = $pdo->prepare("SELECT 
    t.*, 
    p.label as priority, 
    p.color as priority_color,
    u.username as user_name,
    u.email as user_email
    FROM support_tickets t
    JOIN ticket_priorities p ON t.priority_id = p.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = "Ticket not found";
    header("Location: tickets.php");
    exit();
}

// Fetch replies
$stmt = $pdo->prepare("SELECT 
    r.*, 
    u.username,
    u.avatar,
    u.role_id
    FROM ticket_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC");
$stmt->execute([$ticketId]);
$replies = $stmt->fetchAll();

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        try {
            $pdo->beginTransaction();
            
            // Add reply
            $stmt = $pdo->prepare("INSERT INTO ticket_replies 
                (ticket_id, user_id, message, is_admin, created_at)
                VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$ticketId, $_SESSION['user_id'], $message]);
            
            // Update status if changed
            if (isset($_POST['status']) && $_POST['status'] !== $ticket['status']) {
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
                $stmt->execute([$_POST['status'], $ticketId]);
                $ticket['status'] = $_POST['status'];
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Reply added successfully";
            
            // Refresh to show new reply
            header("Location: ticket_view.php?id=$ticketId");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error adding reply: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Message cannot be empty";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .ticket-container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .ticket-header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .ticket-meta { display: flex; gap: 15px; margin-bottom: 10px; flex-wrap: wrap; }
        .ticket-priority { padding: 3px 10px; border-radius: 4px; color: white; font-weight: bold; font-size: 0.9em; }
        .ticket-status { padding: 3px 10px; border-radius: 4px; background: #e9ecef; text-transform: capitalize; }
        .ticket-body { padding: 20px; background: white; border-radius: 5px; margin-bottom: 30px; }
        .reply { background: white; padding: 15px; margin-bottom: 15px; border-radius: 5px; border-left: 3px solid #ddd; }
        .reply.admin { border-left-color: #3498db; }
        .reply-form { background: #f8f9fa; padding: 20px; border-radius: 5px; }
        .reply-form textarea { width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .status-select { padding: 8px; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></h1>
                <div class="header-actions">
                    <a href="tickets.php" class="btn">Back to Tickets</a>
                </div>
            </header>
            
            <div class="content">
                <div class="ticket-container">
                    <!-- Display any messages -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <!-- Ticket Header -->
                    <div class="ticket-header">
                        <div class="ticket-meta">
                            <div class="ticket-priority" style="background: <?= $ticket['priority_color'] ?>">
                                <?= htmlspecialchars($ticket['priority']) ?>
                            </div>
                            <div class="ticket-status">
                                <?= htmlspecialchars($ticket['status']) ?>
                            </div>
                            <div>Created: <?= date('M j, Y g:i a', strtotime($ticket['created_at'])) ?></div>
                        </div>
                        <h2><?= htmlspecialchars($ticket['subject']) ?></h2>
                        <p>From: <?= htmlspecialchars($ticket['user_name']) ?> (<?= htmlspecialchars($ticket['user_email']) ?>)</p>
                    </div>
                    
                    <!-- Ticket Content -->
                    <div class="ticket-body">
                        <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                        
                        <?php if (!empty($ticket['attachment'])): ?>
                            <div class="ticket-attachment">
                                <strong>Attachment:</strong>
                                <a href="../uploads/tickets/<?= htmlspecialchars($ticket['attachment']) ?>" download>
                                    Download File
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Replies -->
                    <h3>Replies (<?= count($replies) ?>)</h3>
                    
                    <?php if (empty($replies)): ?>
                        <p>No replies yet</p>
                    <?php else: ?>
                        <?php foreach ($replies as $reply): ?>
                            <div class="reply <?= $reply['is_admin'] ? 'admin' : '' ?>">
                                <div class="reply-meta">
                                    <strong><?= htmlspecialchars($reply['username']) ?></strong>
                                    <span><?= date('M j, Y g:i a', strtotime($reply['created_at'])) ?></span>
                                </div>
                                <div class="reply-content">
                                    <?= nl2br(htmlspecialchars($reply['message'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Reply Form -->
                    <div class="reply-form">
                        <h3>Add Reply</h3>
                        <form method="POST">
                            <div class="form-group">
                                <textarea name="message" required placeholder="Type your reply here..."></textarea>
                            </div>
                            <div class="form-group">
                                <label for="status">Update Status:</label>
                                <select name="status" id="status" class="status-select">
                                    <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="on_hold" <?= $ticket['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                                    <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Reply</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Simple textarea auto-resize
    document.querySelector('textarea').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    </script>
</body>
</html>