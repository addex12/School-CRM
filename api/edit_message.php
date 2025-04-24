<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'], $data['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$messageId = (int)$data['id'];
$newMessage = trim($data['message']);
$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;

// Fetch message
$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$messageId]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Message not found']);
    exit;
}

// Only sender or admin can edit
if ($message['sender_id'] != $userId && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Update message
$stmt = $pdo->prepare("UPDATE messages SET message = ?, edited_at = NOW() WHERE id = ?");
$success = $stmt->execute([$newMessage, $messageId]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to edit message']);
}
