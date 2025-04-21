<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        // Optionally, handle broadcast messages if you support them
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE receiver_id = 'broadcast' ORDER BY sent_at ASC");
        $stmt->execute();
    } else {
        // Fetch messages between current user and selected user
        $stmt = $pdo->prepare(
            "SELECT m.*, 
                    u.username AS sender 
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             WHERE (m.sender_id = :current_user1 AND m.receiver_id = :user_id1)
                OR (m.sender_id = :user_id2 AND m.receiver_id = :current_user2)
             ORDER BY m.sent_at ASC"
        );
        $stmt->execute([
            'current_user1' => $current_user_id,
            'user_id1' => $user_id,
            'user_id2' => $user_id,
            'current_user2' => $current_user_id
        ]);
    }
    $messages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender'],
            'message' => $row['message'],
            'sent_at' => $row['sent_at'],
            'is_own' => $row['sender_id'] == $current_user_id
        ];
    }
    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
