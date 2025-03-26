<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Fetch emails for the logged-in user
$userId = $_SESSION['user_id'];
$query = "SELECT sender, subject, message, created_at FROM emails WHERE recipient_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox</title>
</head>
<body>
    <h1>Your Inbox</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Sender</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Received At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($email = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($email['sender']); ?></td>
                    <td><?php echo htmlspecialchars($email['subject']); ?></td>
                    <td><?php echo htmlspecialchars($email['message']); ?></td>
                    <td><?php echo htmlspecialchars($email['created_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
