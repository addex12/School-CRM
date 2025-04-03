<?php
/**
 * View Support Ticket
 * This page is specifically for viewing the details of a single ticket.
 * It is not intended for browsing or listing multiple tickets.
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "View Ticket";

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

// Fetch ticket details
try {
    $stmt = $pdo->prepare("SELECT st.*, tp.label as priority_label, tp.color as priority_color, 
                          u.username as user_name, u.email as user_email, u.avatar as user_avatar,
                          a.username as assigned_to_name, a.email as assigned_to_email, a.avatar as assigned_to_avatar
                          FROM support_tickets st
                          JOIN ticket_priorities tp ON st.priority_id = tp.id
                          JOIN users u ON st.user_id = u.id
                          LEFT JOIN users a ON st.assigned_to = a.id
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
    $_SESSION['error'] = "Failed to fetch ticket details.";
    header("Location: tickets.php");
    exit();
}

// Fetch ticket replies
try {
    $stmt = $pdo->prepare("SELECT tr.*, u.username, u.avatar, u.role_id, r.role_name
                          FROM ticket_replies tr
                          JOIN users u ON tr.user_id = u.id
                          JOIN roles r ON u.role_id = r.id
                          WHERE tr.ticket_id = ?
                          ORDER BY tr.created_at ASC");
    $stmt->execute([$ticketId]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Ticket replies fetch error: " . $e->getMessage());
    $replies = [];
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $message = trim($_POST['reply_message']);
    
    if (!empty($message)) {
        try {
            $pdo->beginTransaction();
            
            // Add reply
            $stmt = $pdo->prepare("INSERT INTO ticket_replies 
                                  (ticket_id, user_id, message, is_admin, created_at)
                                  VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$ticketId, $_SESSION['user_id'], $message]);
            
            // Update ticket status if needed
            if (isset($_POST['update_status'])) {
                $newStatus = $_POST['ticket_status'];
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $ticketId]);
                $ticket['status'] = $newStatus;
            }
            
            // Add activity log
            $activityDesc = "Replied to ticket #" . $ticket['ticket_number'];
            $stmt = $pdo->prepare("INSERT INTO activity_log 
                                  (user_id, activity_type, description, ip_address, created_at)
                                  VALUES (?, 'ticket', ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $activityDesc, $_SERVER['REMOTE_ADDR']]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Reply added successfully.";
            header("Location: ticket_view.php?id=$ticketId");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Ticket reply error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to add reply. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Reply message cannot be empty.";
    }
}

// Get available statuses
$statuses = ['open', 'in_progress', 'on_hold', 'resolved'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/tickets.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Viewing ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></p>
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
                <!-- Ticket Details Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-ticket-alt"></i> Ticket Details</h2>
                        <div class="ticket-actions">
                            <a href="tickets.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Back to Tickets
                            </a>
                            <a href="ticket_edit.php?id=<?= $ticketId ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Edit Ticket
                            </a>
                        </div>
                    </div>
                    
                    <div class="ticket-details">
                        <div class="ticket-header">
                            <div class="ticket-meta">
                                <div class="ticket-priority" style="background-color: <?= $ticket['priority_color'] ?>">
                                    <?= htmlspecialchars($ticket['priority_label']) ?>
                                </div>
                                <div class="ticket-status badge-<?= str_replace('_', '-', $ticket['status']) ?>">
                                    <?= ucwords(str_replace('_', ' ', $ticket['status'])) ?>
                                </div>
                                <div class="ticket-date">
                                    <i class="far fa-calendar-alt"></i> <?= formatDate($ticket['created_at']) ?>
                                </div>
                            </div>
                            <h3 class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></h3>
                        </div>
                        
                        <div class="ticket-body">
                            <div class="ticket-author">
                                <div class="author-avatar">
                                    <img src="../uploads/avatars/<?= htmlspecialchars($ticket['user_avatar'] ?: 'default.jpg') ?>" alt="User Avatar">
                                </div>
                                <div class="author-info">
                                    <h4><?= htmlspecialchars($ticket['user_name']) ?></h4>
                                    <p><?= htmlspecialchars($ticket['user_email']) ?></p>
                                </div>
                            </div>
                            <div class="ticket-content">
                                <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                
                                <?php if (!empty($ticket['attachment'])): ?>
                                    <div class="ticket-attachment">
                                        <h5><i class="fas fa-paperclip"></i> Attachment:</h5>
                                        <a href="../uploads/tickets/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" class="attachment-link">
                                            <i class="fas fa-file-download"></i> Download File
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket Replies Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-comments"></i> Conversation</h2>
                        <span class="reply-count"><?= count($replies) ?> replies</span>
                    </div>
                    
                    <div class="ticket-replies">
                        <?php if (!empty($replies)): ?>
                            <?php foreach ($replies as $reply): ?>
                                <div class="reply-item <?= $reply['is_admin'] ? 'admin-reply' : 'user-reply' ?>">
                                    <div class="reply-author">
                                        <div class="author-avatar">
                                            <img src="../uploads/avatars/<?= htmlspecialchars($reply['avatar'] ?: 'default.jpg') ?>" alt="User Avatar">
                                        </div>
                                        <div class="author-info">
                                            <h4><?= htmlspecialchars($reply['username']) ?></h4>
                                            <p class="role-badge role-<?= $reply['role_id'] ?>">
                                                <?= htmlspecialchars($reply['role_name']) ?>
                                            </p>
                                            <small><?= formatDate($reply['created_at']) ?></small>
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
                    
                    <!-- Reply Form -->
                    <div class="reply-form-section">
                        <h3><i class="fas fa-reply"></i> Add Reply</h3>
                        <form method="POST" class="reply-form">
                            <div class="form-group">
                                <textarea name="reply_message" id="reply_message" rows="5" placeholder="Type your reply here..." required></textarea>
                            </div>
                            <div class="form-footer">
                                <div class="form-actions">
                                    <div class="status-update">
                                        <label for="ticket_status">Update Status:</label>
                                        <select name="ticket_status" id="ticket_status">
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>>
                                                    <?= ucwords(str_replace('_', ' ', $status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="checkbox" name="update_status" id="update_status">
                                        <label for="update_status" class="checkbox-label">Apply</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Submit Reply
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/ticket_view.js"></script>
</body>
</html>