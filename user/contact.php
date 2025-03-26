<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/../includes/file_upload.php';
requireLogin();

// Initialize variables
$error = $success = '';
$subject = $message = $priority = '';
$attachment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Handle file upload
    if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        try {
            $attachment = handleFileUpload('attachment');
        } catch (Exception $e) {
            $error = "File upload error: " . $e->getMessage();
        }
    }

    if (empty($error)) {
        try {
            // Generate ticket number
            $ticket_number = 'TKT-' . strtoupper(string: uniqid());
            
            $stmt = $pdo->prepare(query: "INSERT INTO support_tickets 
                                (user_id, ticket_number, subject, message, priority, attachment) 
                                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(params: [$_SESSION['user_id'], $ticket_number, $subject, $message, $priority, $attachment]);
            
            // Send confirmation email
            $user_email = $_SESSION['email'];
            sendEmail(to: $user_email, subject: "Ticket Created: $ticket_number", 
                body: "Your support ticket has been created.\n\nTicket Number: $ticket_number");
            
            // Notify admins
            $admin_subject = "New Support Ticket: $ticket_number";
            $admin_body = "Priority: $priority\nSubject: $subject\nMessage: $message";
            if ($attachment) {
                $admin_body .= "\n\nAttachment: " . $ticket['attachment'];
            }
            sendEmailToAdmins($admin_subject, $admin_body);
            
            $success = "Ticket created successfully! Check your email for confirmation.";
            
            // Clear form on success
            $subject = $message = '';
            $priority = 'medium';
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="../assets/js/contact.js" defer></script>
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>

        <div class="content">
            <h2>Contact Support</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form id="support-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($subject) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority" required>
                        <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= $priority === 'medium' || empty($priority) ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="5" required><?= htmlspecialchars($message) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Attachment (optional):</label>
                    <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.txt,.doc,.docx">
                    <small class="file-hint">Max file size: 5MB (PDF, JPG, PNG, TXT, DOC/DOCX)</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </form>

            <div class="ticket-history">
                <h3>Your Support Tickets</h3>
                <?php if ($tickets->rowCount() > 0): ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket">
                            <div class="ticket-header">
                                <span class="ticket-number"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
                                <span class="priority <?= htmlspecialchars($ticket['priority']) ?>">
                                    <?= ucfirst(htmlspecialchars($ticket['priority'])) ?>
                                </span>
                            </div>
                            <h4><?= htmlspecialchars($ticket['subject']) ?></h4>
                            <p><?= nl2br(htmlspecialchars($ticket['message'])) ?></p>
                            <?php if ($ticket['attachment']): ?>
                                <div class="attachment">
                                    <a href="../uploads/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank">
                                        <i class="fas fa-paperclip"></i> Attachment
                                    </a>
                                </div>
                            <?php endif; ?>
                            <small>Created: <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>You haven't submitted any support tickets yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>