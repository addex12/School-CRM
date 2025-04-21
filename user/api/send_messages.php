<?php
require_once '../../includes/auth.php';
require_once '../../includes/config.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$current_user_id = $_SESSION['user_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!$current_user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing receiver_id or message']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, sent_at, is_read) 
        VALUES (:sender_id, :receiver_id, :content, NOW(), 0)
    ");
    
    $stmt->execute([
        ':sender_id' => $current_user_id,
        ':receiver_id' => $receiver_id,
        ':content' => $message
    ]);

    echo json_encode([
        'success' => true,
        'message_id' => $pdo->lastInsertId(),
        'debug' => [
            'sender' => $current_user_id,
            'receiver' => $receiver_id
        ]
    ]);
} catch (PDOException $e) {
    error_log("Send Message Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'debug' => $e->getMessage()
    ]);
}