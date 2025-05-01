<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

global $pdo;

// Validate and retrieve ticket ID
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id || !is_numeric($ticket_id)) {
    header('Location: contact.php?error=Ticket ID is required and must be valid.');
    exit;
}

// Fetch ticket details
$stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE id = :id');
$stmt->execute([':id' => $ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: contact.php?error=Ticket not found.');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';

    if (!empty($subject) && !empty($message)) {
        $stmt = $pdo->prepare('UPDATE support_tickets SET subject = :subject, message = :message, priority = :priority WHERE id = :id');
        $stmt->execute([
            ':subject' => $subject,
            ':message' => $message,
            ':priority' => $priority,
            ':id' => $ticket_id
        ]);
        header('Location: contact.php?success=Ticket updated successfully.');
        exit;
    }
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<style>
/* Adugna: Edit Ticket Page Custom Styles - Compact, Responsive, and Branded */
.adugna-main-content-container {
    max-width: 800px;
    margin: 40px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    padding: 18px 10px 24px 10px;
}
.adugna-form-group {
    margin-bottom: 15px;
}
.adugna-label {
    font-weight: 500;
    color: #1a1a1a;
    margin-bottom: 4px;
    display: block;
}
.adugna-input, .adugna-textarea, .adugna-select {
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
.adugna-input:focus, .adugna-textarea:focus, .adugna-select:focus {
    outline: none;
    border-color: #1a73e8;
    background: #fff;
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
@media (max-width: 700px) {
    .adugna-main-content-container {
        padding: 8px 2vw;
    }
}
@media (max-width: 400px) {
    .adugna-main-content-container {
        padding: 2px 0.5vw;
    }
}
</style>

<div class="adugna-main-content-container">
    <h2 style="color:#1a73e8;font-size:1.13em;margin-bottom:18px;"><i class="fas fa-edit" style="font-size:1em;margin-right:4px;"></i> Edit Ticket</h2>
    <form method="POST">
        <div class="adugna-form-group">
            <label for="subject" class="adugna-label">Subject</label>
            <input type="text" name="subject" id="subject" value="<?= htmlspecialchars($ticket['subject']) ?>" required class="adugna-input">
        </div>
        <div class="adugna-form-group">
            <label for="message" class="adugna-label">Message</label>
            <textarea name="message" id="message" rows="5" required class="adugna-textarea"><?= htmlspecialchars($ticket['message']) ?></textarea>
        </div>
        <div class="adugna-form-group">
            <label for="priority" class="adugna-label">Priority</label>
            <select name="priority" id="priority" class="adugna-select">
                <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $ticket['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                <option value="critical" <?= $ticket['priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>
        </div>
        <button type="submit" class="adugna-btn adugna-btn-primary"><i class="fas fa-save"></i> Update Ticket</button>
    </form>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
