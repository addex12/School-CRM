<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id']);
    exit;
}

$user_id = $_GET['user_id'];
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$current_user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
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
        ':sender_id' => $user_id,
        ':receiver_id' => $current_user_id
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}