<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing user_id parameter']);
    exit;
}

$current_user_id = $_SESSION['user_id'] ?? null;
$other_user_id = $_GET['user_id'];

if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    if ($other_user_id === 'broadcast') {
        // Handle broadcast messages (admin to all users)
        $stmt = $pdo->prepare("
            SELECT m.*, u.username as sender 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = :current_user_id 
            AND m.is_admin = 1
            ORDER BY m.sent_at ASC
        ");
        
        $stmt->execute([
            ':current_user_id' => $current_user_id
        ]);
    } else {
        // Handle one-to-one conversations
        $stmt = $pdo->prepare("
            SELECT m.*, u.username as sender 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = :current_user_id AND m.receiver_id = :other_user_id)
               OR (m.sender_id = :other_user_id AND m.receiver_id = :current_user_id)
            ORDER BY m.sent_at ASC
        ");
        
        $stmt->execute([
            ':current_user_id' => $current_user_id,
            ':other_user_id' => $other_user_id
        ]);
    }

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

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'debug' => [
            'current_user' => $current_user_id,
            'other_user' => $other_user_id
        ]
    ]);

} catch (PDOException $e) {
    error_log('Message query error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'debug' => $e->getMessage()
    ]);
}