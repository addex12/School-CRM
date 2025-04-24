<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Get POST data
$contact_id = $_POST['contact_id'] ?? null;
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!$contact_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing contact_id']);
    exit;
}

try {
    if ($contact_id === 'broadcast') {
        // Handle broadcast messages
        $query = "SELECT m.*, u.username as sender 
                 FROM messages m
                 JOIN users u ON m.sender_id = u.id
                 WHERE m.receiver_id = :current_user_id 
                 AND m.sender_id = 1
                 ORDER BY m.sent_at ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['current_user_id' => $current_user_id]);
    } else {
        // Handle one-to-one conversations
        $query = "SELECT m.*, u.username as sender 
                 FROM messages m
                 JOIN users u ON m.sender_id = u.id
                 WHERE ((m.sender_id = :current_user_id AND m.receiver_id = :contact_id)
                 OR (m.sender_id = :contact_id AND m.receiver_id = :current_user_id))
                 AND (m.deleted_by_sender = 0 OR m.sender_id != :current_user_id)
                 AND (m.deleted_by_receiver = 0 OR m.receiver_id != :current_user_id)
                 ORDER BY m.sent_at ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'current_user_id' => $current_user_id,
            'contact_id' => $contact_id
        ]);
    }

    $messages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender'],
            'content' => $row['content'],
            'sent_at' => date('M j, Y g:i a', strtotime($row['sent_at'])),
            'is_own' => $row['sender_id'] == $current_user_id
        ];
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'debug_info' => $e->getMessage()
    ]);
}