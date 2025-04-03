<?php
/**
 * Handle ticket replies
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ticket_id'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: tickets.php");
    exit();
}

$ticketId = (int)$_POST['ticket_id'];
$message = trim($_POST['message'] ?? '');

// Basic validation
if (empty($message)) {
    $_SESSION['error'] = "Reply message cannot be empty.";
    header("Location: ticket_edit.php?id=$ticketId");
    exit();
}

try {
    // Insert reply
    $stmt = $pdo->prepare("INSERT INTO ticket_replies 
                          (ticket_id, user_id, message, is_admin, created_at)
                          VALUES (?, ?, ?, 1, NOW())");
    $stmt->execute([$ticketId, $_SESSION['user_id'], $message]);
    
    // Update ticket status to "in_progress" if it was "open"
    $stmt = $pdo->prepare("UPDATE support_tickets 
                          SET status = 'in_progress', updated_at = NOW()
                          WHERE id = ? AND status = 'open'");
    $stmt->execute([$ticketId]);
    
    // Log activity
    logActivity($pdo, "Replied to ticket #" . $ticketId, 'ticket', $_SESSION['user_id']);
    
    $_SESSION['success'] = "Reply added successfully.";
} catch (Exception $e) {
    error_log("Ticket reply error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to add reply.";
}

header("Location: ticket_edit.php?id=$ticketId");
exit();