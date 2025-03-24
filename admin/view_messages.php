<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$messages = $conn->query("SELECT id, sender_id, receiver_id, message, sent_at FROM messages");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Messages</title>
</head>
<body>
    <h1>Messages</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Sender ID</th>
            <th>Receiver ID</th>
            <th>Message</th>
            <th>Sent At</th>
        </tr>
        <?php while ($message = $messages->fetch_assoc()): ?>
        <tr>
            <td><?php echo $message['id']; ?></td>
            <td><?php echo $message['sender_id']; ?></td>
            <td><?php echo $message['receiver_id']; ?></td>
            <td><?php echo htmlspecialchars($message['message']); ?></td>
            <td><?php echo $message['sent_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
