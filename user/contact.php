<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Get user information
$user_id = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['email'] ?? '';
?>

<div class="container">
<?php include_once __DIR__ . '/includes/header.php';
?>
    <h2>Contact Support</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Your support ticket has been submitted successfully. Ticket #<?= htmlspecialchars($_GET['ticket']) ?>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            Error submitting your request: <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>
    
    <form id="contact-form" action="contact-submit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        
        <div class="form-group">
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user_email) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority</label>
            <select id="priority" name="priority" class="form-control" required>
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="attachment">Attachment (if any)</label>
            <input type="file" id="attachment" name="attachment" class="form-control-file">
            <small class="text-muted">Max 5MB (PDF, JPG, PNG, DOCX allowed)</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Submit Ticket</button>
    </form>
    <?php include_once __DIR__ . '/includes/footer.php'; ?>
</div>

