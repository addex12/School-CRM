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

global $pdo;

// Get user information
$user_id = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['email'] ?? '';

// Fetch user's support tickets
$tickets = [];
if ($user_id) {
    $stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE user_id = :user_id ORDER BY created_at DESC');
    $stmt->execute([':user_id' => $user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
/* Adugna: Contact Support Page Custom Styles - Compact, Responsive, and Branded */
.adugna-contact-main-container {
    max-width: 600px;
    margin: 40px auto 0 auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    padding: 18px 10px 24px 10px;
}
.adugna-contact-header {
    text-align: center;
    margin-bottom: 24px;
}
.adugna-contact-header h2 {
    color: #1a73e8;
    font-size: 1.18rem;
    margin-bottom: 6px;
}
.adugna-contact-header i {
    margin-right: 7px;
    font-size: 1.08rem;
}
.adugna-contact-form .adugna-form-group {
    margin-bottom: 15px;
}
body, input, textarea, select, button {
    font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
    font-size: 15px;
}
.adugna-btn {
    background: #f5f7fa;
    color: #1a1a1a;
    border: 1.2px solid #d1d8dd;
    border-radius: 4px;
    padding: 4px 10px;
    font-size: 0.89rem;
    font-weight: 500;
    transition: background 0.18s, color 0.18s;
    cursor: pointer;
    min-width: 80px;
    min-height: 28px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.adugna-btn.adugna-btn-primary {
    background: #1a73e8;
    color: #fff;
    border-color: #1a73e8;
}
.adugna-btn.adugna-btn-primary:hover {
    background: #155ab6;
    color: #fff;
}
.adugna-input, .adugna-textarea {
    border: 1.2px solid #d1d8dd;
    border-radius: 4px;
    padding: 6px 10px;
    font-size: 0.89rem;
    background: #f5f7fa;
    color: #1a1a1a;
    width: 100%;
    box-sizing: border-box;
    margin-bottom: 2px;
}
.adugna-input:focus, .adugna-textarea:focus {
    outline: none;
    border-color: #1a73e8;
    background: #fff;
}
.adugna-label {
    font-weight: 500;
    color: #1a1a1a;
    margin-bottom: 4px;
    display: block;
}
@media (max-width: 700px) {
    .adugna-contact-main-container {
        padding: 10px 2vw;
    }
}
.adugna-main-content-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 0 20px;
}
.adugna-ticket-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 18px;
}
.adugna-ticket-table th, .adugna-ticket-table td {
    border: 1px solid #e3e8ef;
    padding: 5px;
    font-size: 0.89rem;
    text-align: left;
}
.adugna-ticket-table th {
    background-color: #f5f7fa;
    color: #1a73e8;
}
.adugna-ticket-actions a {
    margin-right: 8px;
    text-decoration: none;
    color: #1a73e8;
    font-size: 0.89rem;
}
.adugna-ticket-actions a:hover {
    text-decoration: underline;
}
</style>

<?php include_once __DIR__ . '/includes/header.php'; ?>
<div class="adugna-main-content-container">
    <div class="adugna-contact-main-container">
        <div class="adugna-contact-header">
            <h2>
                <i class="fas fa-headset"></i> Contact Support
            </h2>
        </div>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Your support ticket has been submitted successfully. 
                <?php if (!empty($_GET['ticket'])): ?>
                    Ticket #<?= htmlspecialchars($_GET['ticket']) ?>
                <?php endif; ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                Error submitting your request: <?= htmlspecialchars($_GET['error'] ?? 'Unknown error') ?>
            </div>
        <?php endif; ?>

        <!-- Support Ticket Form -->
        <form id="contact-form" class="adugna-contact-form" action="contact-submit.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?= $user_id ?>">
            <div class="adugna-form-group">
                <label class="adugna-label" for="email">Your Email</label>
                <input type="email" id="email" name="email" class="adugna-input" value="<?= htmlspecialchars($user_email) ?>" required>
            </div>
            <div class="adugna-form-group">
                <label class="adugna-label" for="subject">Subject</label>
                <select id="subject" name="subject" class="adugna-input" required onchange="toggleCustomSubject(this)">
                    <option value="" disabled selected>Select a subject</option>
                    <option value="Technical Issue">Technical Issue</option>
                    <option value="Billing Inquiry">Billing Inquiry</option>
                    <option value="Account Access">Account Access</option>
                    <option value="Feature Request">Feature Request</option>
                    <option value="Feedback">Feedback</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" id="custom-subject" name="custom_subject" class="adugna-input" placeholder="Enter custom subject" style="display:none; margin-top:10px;">
            </div>
            <div class="adugna-form-group">
                <label class="adugna-label" for="priority">Priority</label>
                <select id="priority" name="priority" class="adugna-input" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div class="adugna-form-group">
                <label class="adugna-label" for="message">Message</label>
                <textarea id="message" name="message" class="adugna-textarea" rows="5" required></textarea>
            </div>
            <div class="adugna-form-group">
                <label class="adugna-label" for="attachment">Attachment (if any)</label>
                <input type="file" id="attachment" name="attachment" class="adugna-input">
                <small class="text-muted">Max 5MB (PDF, JPG, PNG, DOCX allowed)</small>
            </div>
            <button type="submit" class="adugna-btn adugna-btn-primary">Submit Ticket</button>
        </form>

        <!-- Display User's Support Tickets -->
        <h3>Your Support Tickets</h3>
        <?php if (count($tickets) > 0): ?>
            <table class="adugna-ticket-table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= htmlspecialchars($ticket['ticket_number']) ?></td>
                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                            <td><?= htmlspecialchars($ticket['priority']) ?></td>
                            <td><?= htmlspecialchars($ticket['status']) ?></td>
                            <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                            <td class="adugna-ticket-actions">
                                <a href="view-ticket.php?id=<?= urlencode($ticket['id']) ?>">View</a>
                                <a href="edit-ticket.php?id=<?= urlencode($ticket['id']) ?>">Edit</a>
                                <a href="delete-ticket.php?id=<?= urlencode($ticket['id']) ?>" onclick="return confirm('Are you sure you want to delete this ticket?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No support tickets found.</p>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
<script src="../includes/activity-tracker.js"></script>
<script>
    // Adugna: Show custom subject input if 'Other' is selected
    function toggleCustomSubject(select) {
        const customSubjectInput = document.getElementById('custom-subject');
        if (select.value === 'Other') {
            customSubjectInput.style.display = 'block';
            customSubjectInput.required = true;
        } else {
            customSubjectInput.style.display = 'none';
            customSubjectInput.required = false;
        }
    }
</script>

