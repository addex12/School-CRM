<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$contact_id = $data['contact_id'] ?? null;
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $messages = [];
    
    if ($contact_id === 'broadcast') {
        // Get broadcast messages sent to current user
        $stmt = $pdo->prepare("
            SELECT m.*, u.username as sender 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = :current_user_id
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute(['current_user_id' => $current_user_id]);
    } else {
        // Get one-to-one messages between current user and contact
        $stmt = $pdo->prepare("
            SELECT m.*, u.username as sender 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (
                (m.sender_id = :current_user_id AND m.receiver_id = :contact_id AND m.deleted_by_sender = 0) 
                OR 
                (m.sender_id = :contact_id AND m.receiver_id = :current_user_id AND m.deleted_by_receiver = 0)
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute([
            'current_user_id' => $current_user_id,
            'contact_id' => $contact_id
        ]);
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender'],
            'content' => $row['content'],
            'sent_at' => date('M j, Y g:i a', strtotime($row['sent_at'])),
            'is_own' => ($row['sender_id'] == $current_user_id),
            'is_read' => $row['is_read']
        ];
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}