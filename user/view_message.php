<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$messageId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'] ?? 0;

if (!$messageId) {
    header("Location: inbox.php?error=Invalid message ID");
    exit;
}

// Fetch the message and mark as read if the user is the receiver
$stmt = $pdo->prepare("
    SELECT m.*, u.username AS sender_name 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.id = ? AND m.receiver_id = ?
");
$stmt->execute([$messageId, $userId]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    header("Location: inbox.php?error=Message not found or access denied");
    exit;
}

// Mark as read if not already
if (!$message['is_read']) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$messageId]);
}
?>
<?php include_once __DIR__ . '/includes/header.php'; ?>
<div class="main-content-container" style="max-width:1000px;margin:0 auto;padding:40px 20px 0 20px;">
    <div class="card" style="padding:32px 24px;max-width:700px;margin:40px auto;">
        <h2 style="color:#007bff;"><i class="fas fa-envelope-open"></i> Message Details</h2>
        <div style="margin-bottom:18px;">
            <strong>From:</strong> <?= htmlspecialchars($message['sender_name'] ?? '') ?><br>
            <strong>Subject:</strong> <?= htmlspecialchars($message['subject'] ?? '') ?><br>
            <strong>Date:</strong> <?= date('M j, Y g:i a', strtotime($message['sent_at'])) ?>
        </div>
        <div style="background:#f8fafd;padding:18px;border-radius:6px;">
            <?= nl2br(htmlspecialchars($message['content'] ?? '')) ?>
        </div>
        <div style="margin-top:24px;">
            <a href="inbox.php" class="erpnext-btn btn-secondary">&larr; Back to Inbox</a>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
<style>
.erpnext-btn {
    background: #f5f7fa;
    color: #36414c;
    border: 1px solid #d1d8dd;
    border-radius: 4px;
    padding: 8px 18px;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
    text-decoration: none;
}
.erpnext-btn.btn-secondary {
    background: #f5f7fa;
    color: #36414c;
    border-color: #d1d8dd;
}
.erpnext-btn.btn-secondary:hover {
    background: #e4e8ec;
}
body, input, textarea, select, button {
    font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
    font-size: 15px;
}
</style>
<script src="../includes/activity-tracker.js"></script>
