<?php
header('Content-Type: application/json');
require_once __DIR__.'/../config.php';

// Validate authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    // Get messages between current user and selected user
    $stmt = $pdo->prepare("SELECT m.*, u.username
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY sent_at ASC
        LIMIT ? OFFSET ?");
    
    $stmt->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id, $limit, $offset]);
    $raw_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $messages = [];
    foreach ($raw_messages as $msg) {
        $messages[] = [
            'is_own' => $msg['sender_id'] == $current_user_id,
            'sender' => $msg['username'],
            'message' => $msg['message'],
            'sent_at' => $msg['sent_at'],
        ];
    }
    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    error_log('Message retrieval error: '.$e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve messages']);
}
