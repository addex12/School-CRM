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

// Don't mark broadcast as read
if ($user_id === 'broadcast') {
    echo json_encode(['success' => true]);
    exit;
}

try {
    // Mark all messages from this user as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE sender_id = :sender_id 
        AND receiver_id = :receiver_id
        AND is_read = 0
    ");
    $stmt->execute([
        'sender_id' => $user_id,
        'receiver_id' => $current_user_id
    ]);
    
    echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}