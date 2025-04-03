<?php
/**
 * Edit Support Ticket
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Edit Ticket";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Check if ticket ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid ticket ID.";
    header("Location: tickets.php");
    exit();
}

$ticketId = (int)$_GET['id'];

// Fetch ticket data
try {
    $stmt = $pdo->prepare("SELECT st.*, u.username, u.email 
                          FROM support_tickets st
                          JOIN users u ON st.user_id = u.id
                          WHERE st.id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['error'] = "Ticket not found.";
        header("Location: tickets.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Ticket fetch error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch ticket data.";
    header("Location: tickets.php");
    exit();
}

// Fetch priorities for dropdown
$priorities = $pdo->query("SELECT * FROM ticket_priorities")->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? 'open';
    $priorityId = (int)($_POST['priority_id'] ?? 2); // Default to medium
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    // Basic validation
    if (empty($subject) || empty($message)) {
        $_SESSION['error'] = "Subject and message are required.";
    } else {
        try {
            // Update ticket
            $stmt = $pdo->prepare("UPDATE support_tickets 
                                  SET subject = ?, message = ?, status = ?, priority_id = ?, admin_notes = ?
                                  WHERE id = ?");
            $stmt->execute([$subject, $message, $status, $priorityId, $adminNotes, $ticketId]);
            
            // Log activity
            logActivity($pdo, "Ticket #{$ticket['ticket_number']} updated", 'ticket', $_SESSION['user_id']);
            
            $_SESSION['success'] = "Ticket updated successfully.";
            header("Location: ticket_view.php?id=$ticketId");
            exit();
        } catch (Exception $e) {
            error_log("Ticket update error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update ticket.";
        }
    }
}

// Fetch ticket replies
$replies = [];
try {
    $stmt = $pdo->prepare("SELECT tr.*, u.username, u.role_id 
                          FROM ticket_replies tr
                          JOIN users u ON tr.user_id = u.id
                          WHERE tr.ticket_id = ?
                          ORDER BY tr.created_at ASC");
    $stmt->execute([$ticketId]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Ticket replies error: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Editing Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
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
                <!-- Ticket Edit Form -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-ticket-alt"></i> Ticket Details</h2>
                        <a href="tickets.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Tickets
                        </a>
                    </div>
                    
                    <form method="post" class="ticket-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="ticket_number">Ticket Number</label>
                                <input type="text" id="ticket_number" value="<?= htmlspecialchars($ticket['ticket_number']) ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="created_at">Created At</label>
                                <input type="text" id="created_at" value="<?= formatDate($ticket['created_at']) ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="username">Submitted By</label>
                                <input type="text" id="username" value="<?= htmlspecialchars($ticket['username']) ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">User Email</label>
                                <input type="text" id="email" value="<?= htmlspecialchars($ticket['email']) ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority_id">Priority</label>
                                <select name="priority_id" id="priority_id" required>
                                    <?php foreach ($priorities as $priority): ?>
                                        <option value="<?= $priority['id'] ?>" 
                                            <?= $priority['id'] == $ticket['priority_id'] ? 'selected' : '' ?>
                                            style="color: <?= $priority['color'] ?>">
                                            <?= htmlspecialchars($priority['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" required>
                                    <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="on_hold" <?= $ticket['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                                    <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="subject">Subject</label>
                                <input type="text" name="subject" id="subject" 
                                       value="<?= htmlspecialchars($ticket['subject']) ?>" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="message">Message</label>
                                <textarea name="message" id="message" rows="5" required><?= htmlspecialchars($ticket['message']) ?></textarea>
                            </div>
                            
                            <?php if (!empty($ticket['attachment'])): ?>
                                <div class="form-group full-width">
                                    <label>Current Attachment</label>
                                    <div class="attachment-preview">
                                        <a href="../uploads/tickets/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank">
                                            <i class="fas fa-paperclip"></i> <?= htmlspecialchars($ticket['attachment']) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group full-width">
                                <label for="admin_notes">Admin Notes (Internal)</label>
                                <textarea name="admin_notes" id="admin_notes" rows="3"><?= htmlspecialchars($ticket['admin_notes'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-actions full-width">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <a href="ticket_view.php?id=<?= $ticketId ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Ticket Replies Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-comments"></i> Ticket Replies</h2>
                    </div>
                    
                    <div class="replies-list">
                        <?php if (!empty($replies)): ?>
                            <?php foreach ($replies as $reply): ?>
                                <div class="reply-item <?= $reply['is_admin'] ? 'admin-reply' : 'user-reply' ?>">
                                    <div class="reply-header">
                                        <div class="reply-user">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="user-info">
                                                <strong><?= htmlspecialchars($reply['username']) ?></strong>
                                                <small><?= $reply['is_admin'] ? 'Admin' : 'User' ?></small>
                                            </div>
                                        </div>
                                        <div class="reply-date">
                                            <?= formatDate($reply['created_at']) ?>
                                        </div>
                                    </div>
                                    <div class="reply-content">
                                        <?= nl2br(htmlspecialchars($reply['message'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-replies">
                                <i class="fas fa-comment-slash"></i>
                                <p>No replies yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add Reply Form -->
                    <form method="post" action="ticket_reply.php" class="reply-form">
                        <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
                        <div class="form-group full-width">
                            <label for="reply_message">Add Reply</label>
                            <textarea name="message" id="reply_message" rows="3" required placeholder="Type your reply here..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-reply"></i> Submit Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/ticket_edit.js"></script>
</body>
</html>