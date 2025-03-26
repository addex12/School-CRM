<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/../includes/file_upload.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $attachment = handleFileUpload('attachment');

    $attachment = null;
if (!empty($_FILES['attachment']['name'])) {
    $attachment = handleFileUpload('attachment');
}
    try {
        // Generate ticket number
        $ticket_number = 'TKT-' . strtoupper(uniqid());
        
        $stmt = $pdo->prepare("INSERT INTO support_tickets 
                            (user_id, ticket_number, subject, message, priority, attachment) 
                            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $ticket_number, $subject, $message, $priority, $attachment]);
        
        // Send confirmation email
        $user_email = $_SESSION['email'];
        sendEmail($user_email, "Ticket Created: $ticket_number", "Your support ticket has been created.\n\nTicket Number: $ticket_number");
        
        // Notify admins
        $admin_subject = "New Support Ticket: $ticket_number";
        $admin_body = "Priority: $priority\nSubject: $subject\nMessage: $message";
        sendEmailToAdmins($admin_subject, $admin_body);
        
        $success = "Ticket created successfully! Check your email for confirmation.";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get ticket history
$tickets = $pdo->prepare("SELECT * FROM support_tickets 
                         WHERE user_id = ? 
                         ORDER BY created_at DESC");
$tickets->execute([$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Support</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>

        <div class="content">
            <h2>Contact Support</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label>Priority:</label>
                    <select name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Attachment:</label>
                    <input type="file" name="attachment">
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </form>

            <div class="ticket-history">
                <h3>Your Support Tickets</h3>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket">
                        <div class="ticket-header">
                            <span class="ticket-number"><?= $ticket['ticket_number'] ?></span>
                            <span class="priority <?= $ticket['priority'] ?>">
                                <?= ucfirst($ticket['priority']) ?>
                            </span>
                        </div>
                        <h4><?= htmlspecialchars($ticket['subject']) ?></h4>
                        <p><?= htmlspecialchars($ticket['message']) ?></p>
                        <?php if ($ticket['attachment']): ?>
                            <div class="attachment">
                                <a href="../uploads/<?= $ticket['attachment'] ?>" target="_blank">
                                    <i class="fas fa-paperclip"></i> Attachment
                                </a>
                            </div>
                        <?php endif; ?>
                        <small>Created: <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>