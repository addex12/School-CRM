<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

header('Content-Type: application/json');

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id parameter']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = $_GET['user_id'];

try {
    if ($other_user_id === 'broadcast') {
        // Not allowed for regular users
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Get conversation between two users
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
           OR (m.sender_id = :user2 AND m.receiver_id = :user1)
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([
        ':user1' => $current_user_id,
        ':user2' => $other_user_id
    ]);

    $messages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender'],
            'message' => $row['content'],
            'sent_at' => date('M j, Y g:i a', strtotime($row['sent_at'])),
            'is_own' => $row['sender_id'] == $current_user_id
        ];
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}