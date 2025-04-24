<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

global $pdo;

$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    header('Location: contact.php?error=Ticket ID is required.');
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
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>
<div class="main-content-container">
    <h2>Ticket Details</h2>
    <p><strong>Ticket #:</strong> <?= htmlspecialchars($ticket['ticket_number']) ?></p>
    <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?></p>
    <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($ticket['message'])) ?></p>
    <p><strong>Priority:</strong> <?= htmlspecialchars($ticket['priority']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($ticket['status']) ?></p>
    <p><strong>Created At:</strong> <?= htmlspecialchars($ticket['created_at']) ?></p>

    <h3>Replies</h3>
    <?php if (count($replies) > 0): ?>
        <?php foreach ($replies as $reply): ?>
            <div>
                <p><strong><?= $reply['user_id'] == $_SESSION['user_id'] ? 'You' : 'Admin' ?>:</strong> <?= nl2br(htmlspecialchars($reply['message'])) ?></p>
                <p><small><?= htmlspecialchars($reply['created_at']) ?></small></p>
            </div>
            <hr>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No replies yet.</p>
    <?php endif; ?>

    <h3>Reply to Ticket</h3>
    <form method="POST">
        <textarea name="reply_message" rows="5" required class="erpnext-textarea"></textarea>
        <br>
        <button type="submit" class="erpnext-btn btn-primary">Submit Reply</button>
    </form>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
