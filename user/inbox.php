<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_connection.php';

$userId = $_SESSION['user_id'];
$query = "SELECT sender, subject, message, created_at FROM emails WHERE recipient_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$emails = [];
while ($email = $result->fetch_assoc()) {
    $emails[] = $email;
}
echo json_encode($emails);
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('inbox.php')
                .then(response => response.json())
                .then(emails => {
                    const tbody = document.querySelector('tbody');
                    emails.forEach(email => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${email.sender}</td>
                            <td>${email.subject}</td>
                            <td>${email.message}</td>
                            <td>${email.created_at}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                });
        });
    </script>
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
        </tbody>
    </table>
</body>
</html>
