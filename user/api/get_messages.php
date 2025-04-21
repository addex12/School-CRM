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

// Validate input
if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id parameter']);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$other_user_id = $_GET['user_id'];

try {
    // For regular users, don't allow viewing admin broadcasts
    if ($other_user_id === 'broadcast') {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Get conversation between two users
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.content as message,
            m.sent_at,
            m.sender_id,
            u.username as sender
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE 
            (m.sender_id = :current_user AND m.receiver_id = :other_user) OR
            (m.sender_id = :other_user AND m.receiver_id = :current_user)
        ORDER BY m.sent_at ASC
    ");
    
    $stmt->execute([
        ':current_user' => $current_user_id,
        ':other_user' => (int)$other_user_id
    ]);

    $messages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender'],
            'message' => $row['message'],
            'sent_at' => date('M j, Y g:i a', strtotime($row['sent_at'])),
            'is_own' => ($row['sender_id'] == $current_user_id)
        ];
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'debug' => [
            'current_user' => $current_user_id,
            'other_user' => $other_user_id,
            'count' => count($messages)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Message Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'debug' => $e->getMessage()
    ]);
}