<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php'; // Ensure this file initializes $pdo

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
body, input, textarea, select, button {
    font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
    font-size: 15px;
}
.erpnext-btn {
    background: #f5f7fa;
    color: #36414c;
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 18px;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
}
.erpnext-btn.btn-primary {
    background: #007bfc;
    color: #fff;
    border-color: #007bfc;
}
.erpnext-btn.btn-primary:hover {
    background: #0056b3;
    color: #fff;
}
.erpnext-input, .erpnext-textarea {
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 15px;
    background: #f5f7fa;
    color: #36414c;
}
.erpnext-input:focus, .erpnext-textarea:focus {
    outline: none;
    border-color: #007bfc;
    background: #fff;
}
.erpnext-label {
    font-weight: 500;
    color: #36414c;
    margin-bottom: 4px;
    display: block;
}
@media (max-width: 700px) {
    .contact-main-container {
        padding: 12px 2vw;
    }
}
.main-content-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 0 20px;
}
</style>

<?php include_once __DIR__ . '/includes/header.php'; ?>
<div class="main-content-container">
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
                <label class="erpnext-label" for="email">Your Email</label>
                <input type="email" id="email" name="email" class="erpnext-input" value="<?= htmlspecialchars($user_email) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="erpnext-label" for="subject">Subject</label>
                <input type="text" id="subject" name="subject" class="erpnext-input" required>
            </div>
            
            <div class="form-group">
                <label class="erpnext-label" for="priority">Priority</label>
                <select id="priority" name="priority" class="erpnext-input" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="erpnext-label" for="message">Message</label>
                <textarea id="message" name="message" class="erpnext-textarea" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="erpnext-label" for="attachment">Attachment (if any)</label>
                <input type="file" id="attachment" name="attachment" class="erpnext-input">
                <small class="text-muted">Max 5MB (PDF, JPG, PNG, DOCX allowed)</small>
            </div>
            
            <button type="submit" class="erpnext-btn btn-primary">Submit Ticket</button>
        </form>
    </div>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
</div>
<script src="../includes/activity-tracker.js"></script>