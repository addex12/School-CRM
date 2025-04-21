<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Get user information
$user_id = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['email'] ?? '';
?>

<style>
.contact-main-container {
    max-width: 600px;
    margin: 40px auto 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    padding: 32px 24px 32px 24px;
}
.contact-header {
    text-align: center;
    margin-bottom: 28px;
}
.contact-header h2 {
    color: #007bff;
    font-size: 2rem;
    margin-bottom: 8px;
}
.contact-header i {
    margin-right: 8px;
}
.contact-form .form-group {
    margin-bottom: 18px;
}
.contact-form label {
    font-weight: 500;
    margin-bottom: 6px;
    display: block;
}
.contact-form input[type="text"],
.contact-form input[type="email"],
.contact-form select,
.contact-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
    background: #f8fafd;
}
.contact-form textarea {
    min-height: 100px;
    resize: vertical;
}
.contact-form .form-control-file {
    padding: 6px 0;
}
.contact-form .btn-primary {
    width: 100%;
    padding: 12px 0;
    font-size: 1.1em;
    border-radius: 4px;
    background: #007bff;
    border: none;
    color: #fff;
    margin-top: 10px;
    transition: background 0.2s;
}
.contact-form .btn-primary:hover {
    background: #0056b3;
}
@media (max-width: 700px) {
    .contact-main-container {
        padding: 12px 2vw;
    }
}
</style>

<div class="container">
    <?php include_once __DIR__ . '/includes/header.php'; ?>
    <div class="contact-main-container">
        <div class="contact-header">
            <h2>
                <i class="fas fa-headset"></i> Contact Support
            </h2>
        </div>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Your support ticket has been submitted successfully. Ticket #<?= htmlspecialchars($_GET['ticket']) ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                Error submitting your request: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form id="contact-form" class="contact-form" action="contact-submit.php" method="POST" enctype="multipart/form-data">
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
    </div>
    <?php include_once __DIR__ . '/includes/footer.php'; ?>
</div>

