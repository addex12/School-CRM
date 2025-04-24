<?php
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
<div class="main-content-container">
    <h2>Edit Ticket</h2>
    <form method="POST">
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject" value="<?= htmlspecialchars($ticket['subject']) ?>" required class="erpnext-input">
        </div>
        <div class="form-group">
            <label for="message">Message</label>
            <textarea name="message" id="message" rows="5" required class="erpnext-textarea"><?= htmlspecialchars($ticket['message']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="priority">Priority</label>
            <select name="priority" id="priority" class="erpnext-input">
                <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $ticket['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                <option value="critical" <?= $ticket['priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>
        </div>
        <button type="submit" class="erpnext-btn btn-primary">Update Ticket</button>
    </form>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
