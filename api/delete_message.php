<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$messageId = (int)$data['id'];
$userId = $_SESSION['user_id'];

// Only allow deleting own messages
$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND sender_id = ?");
$stmt->execute([$messageId, $userId]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    echo json_encode(['success' => false, 'error' => 'Message not found or permission denied']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
if ($stmt->execute([$messageId])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
}
