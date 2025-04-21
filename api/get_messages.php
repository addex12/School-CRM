<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing user_id']);
    exit;
}

$user_id = $_GET['user_id'];
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    if ($user_id === 'broadcast') {
        // Get broadcast messages
        $stmt = $pdo->prepare("
            SELECT m.*, u.username as sender 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = :current_user AND m.sender_id = :admin_id
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute([
            'current_user' => $current_user_id,
            'admin_id' => 1 // Assuming admin has ID 1
        ]);
    } else {
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
            'user1' => $current_user_id,
            'user2' => $user_id
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
    
    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}