<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $messageId = $data['messageId'] ?? null;

    if ($messageId) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$messageId, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
    }
} catch (PDOException $e) {
    error_log("Error marking message as read: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}