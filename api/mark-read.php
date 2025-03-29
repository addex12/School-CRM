<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate input
    if (!isset($data['messageId']) || !is_numeric($data['messageId'])) {
        throw new Exception('Invalid message ID');
    }

    $messageId = (int)$data['messageId'];
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        throw new Exception('User not authenticated');
    }

    // Update the message as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE id = ? AND receiver_id = ?
    ");
    $stmt->execute([$messageId, $userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Message not found or not authorized');
    }

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}