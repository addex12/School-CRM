<?php
header('Content-Type: application/json');
require_once __DIR__.'/../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'unread' => $result['unread_count']]);
} catch (PDOException $e) {
    error_log('Notification error: '.$e->getMessage());
    echo json_encode(['success' => false]);
}
