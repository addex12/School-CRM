<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$userId = $_SESSION['user_id'];

// Fetch messages for the inbox
$stmt = $pdo->prepare("
    SELECT m.id, m.subject, m.content, m.sender_id, m.receiver_id, m.created_at 
    FROM messages m
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$userId]);
$messages = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h1>Your Inbox</h1>

<table>
    <thead>
        <tr>
            <th>Sender</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($messages as $message): ?>
        <tr>
            <td><?= htmlspecialchars($message['sender_name']) ?></td>
            <td><?= htmlspecialchars($message['subject']) ?></td>
            <td><?= htmlspecialchars($message['content']) ?></td>
            <td><?= date('M j, Y g:i a', strtotime($message['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>