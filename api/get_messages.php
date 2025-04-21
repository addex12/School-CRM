<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Debugging: Log initial request
error_log("GET: " . print_r($_GET, true));
error_log("SESSION: " . print_r($_SESSION, true));

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
    // Verify database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    if ($other_user_id === 'broadcast') {
        // Handle broadcast messages
        $query = "SELECT m.*, u.username as sender 
                 FROM messages m
                 JOIN users u ON m.sender_id = u.id
                 WHERE m.receiver_id = :current_user_id 
                 AND m.is_admin = 1
                 ORDER BY m.sent_at ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
    } else {
        // Handle one-to-one conversations
        $query = "SELECT m.*, u.username as sender 
                 FROM messages m
                 JOIN users u ON m.sender_id = u.id
                 WHERE (m.sender_id = :current_user_id AND m.receiver_id = :other_user_id)
                 OR (m.sender_id = :other_user_id AND m.receiver_id = :current_user_id)
                 ORDER BY m.sent_at ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':other_user_id', $other_user_id, PDO::PARAM_INT);
    }

    if (!$stmt->execute()) {
        $error = $stmt->errorInfo();
        throw new Exception("Query failed: " . $error[2]);
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

    // Debug output
    error_log("Retrieved messages: " . count($messages));
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'debug_info' => [
            'current_user' => $current_user_id,
            'other_user' => $other_user_id,
            'message_count' => count($messages)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_messages: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'current_user' => $current_user_id,
            'other_user' => $other_user_id
        ]
    ]);
}