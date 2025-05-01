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

// Fetch replies
$replies_stmt = $pdo->prepare('SELECT * FROM ticket_replies WHERE ticket_id = :ticket_id ORDER BY created_at ASC');
$replies_stmt->execute([':ticket_id' => $ticket_id]);
$replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply_message = $_POST['reply_message'] ?? '';

    if (!empty($reply_message)) {
        $stmt = $pdo->prepare('INSERT INTO ticket_replies (ticket_id, user_id, message, created_at) VALUES (:ticket_id, :user_id, :message, NOW())');
        $stmt->execute([
            ':ticket_id' => $ticket_id,
            ':user_id' => $_SESSION['user_id'],
            ':message' => $reply_message
        ]);
        header("Location: view-ticket.php?id=$ticket_id");
        exit;
    }
}

/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<style>
/* Adugna: Ticket View Page Custom Styles - Compact, Responsive, and Branded */
.adugna-main-content-container {
    max-width: 800px;
    margin: 40px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    padding: 18px 10px 24px 10px;
}
.adugna-ticket-header {
    font-size: 1.18rem;
    color: #1a73e8;
    margin-bottom: 10px;
    font-weight: 600;
}
.adugna-ticket-detail-row {
    margin-bottom: 7px;
    font-size: 0.98rem;
}
.adugna-ticket-label {
    font-weight: 500;
    color: #36414c;
    min-width: 110px;
    display: inline-block;
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
    min-width: 70px;
    min-height: 26px;
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
.adugna-reply-list {
    margin: 18px 0 10px 0;
}
.adugna-reply-item {
    background: #f7fafd;
    border-radius: 7px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    padding: 10px 10px 8px 10px;
    margin-bottom: 10px;
    border: 1.2px solid #e3e8ef;
    font-size: 0.96rem;
}
.adugna-reply-meta {
    color: #888;
    font-size: 0.82em;
    margin-top: 2px;
}
.adugna-form-textarea {
    width: 100%;
    border: 1.2px solid #d1d8dd;
    border-radius: 4px;
    padding: 6px 10px;
    font-size: 0.93rem;
    background: #f5f7fa;
    color: #1a1a1a;
    margin-bottom: 8px;
    resize: vertical;
    min-height: 70px;
}
.adugna-form-textarea:focus {
    outline: none;
    border-color: #1a73e8;
    background: #fff;
}
@media (max-width: 700px) {
    .adugna-main-content-container {
        padding: 8px 2vw;
    }
    .adugna-ticket-header {
        font-size: 1.05rem;
    }
}
@media (max-width: 400px) {
    .adugna-main-content-container {
        padding: 2px 0.5vw;
    }
    .adugna-ticket-header {
        font-size: 0.98rem;
    }
}
</style>

<div class="adugna-main-content-container">
    <div class="adugna-ticket-header">
        <i class="fas fa-ticket-alt" style="font-size:1em;margin-right:4px;"></i> Ticket Details
    </div>
    <div class="adugna-ticket-detail-row"><span class="adugna-ticket-label">Ticket #:</span> <?= htmlspecialchars($ticket['ticket_number']) ?></div>
    <div class="adugna-ticket-detail-row"><span class="adugna-ticket-label">Subject:</span> <?= htmlspecialchars($ticket['subject']) ?></div>
    <div class="adugna-ticket-detail-row"><span class="adugna-ticket-label">Message:</span> <?= nl2br(htmlspecialchars($ticket['message'])) ?></div>
    <div class="adugna-ticket-detail-row"><span class="adugna-ticket-label">Priority:</span> <?= htmlspecialchars($ticket['priority']) ?></div>
    <div class="adugna-ticket-detail-row"><span class="adugna-ticket-label">Status:</span> <?= htmlspecialchars($ticket['status']) ?></div>
    <div class="adugna-ticket-detail-row"><span class="adugna-ticket-label">Created At:</span> <?= htmlspecialchars($ticket['created_at']) ?></div>

    <div class="adugna-ticket-header" style="font-size:1.05rem;margin-top:18px;">
        <i class="fas fa-comments"></i> Replies
    </div>
    <div class="adugna-reply-list">
    <?php if (count($replies) > 0): ?>
        <?php foreach ($replies as $reply): ?>
            <div class="adugna-reply-item">
                <div><strong><?= $reply['user_id'] == $_SESSION['user_id'] ? 'You' : 'Admin' ?>:</strong> <?= nl2br(htmlspecialchars($reply['message'])) ?></div>
                <div class="adugna-reply-meta"><i class="fas fa-clock"></i> <?= htmlspecialchars($reply['created_at']) ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="adugna-reply-item">No replies yet.</div>
    <?php endif; ?>
    </div>

    <div class="adugna-ticket-header" style="font-size:1.05rem;margin-top:18px;">
        <i class="fas fa-reply"></i> Reply to Ticket
    </div>
    <form method="POST">
        <textarea name="reply_message" rows="5" required class="adugna-form-textarea"></textarea>
        <br>
        <button type="submit" class="adugna-btn adugna-btn-primary"><i class="fas fa-paper-plane"></i> Submit Reply</button>
    </form>

    <!-- Back and Cancel Links -->
    <div style="margin-top: 20px;">
        <a href="contact.php" class="adugna-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="contact.php" class="adugna-btn adugna-btn-primary"><i class="fas fa-times"></i> Cancel</a>
    </div>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
