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
$bothSides = !empty($data['both_sides']); // true if deleting for both sender and receiver

// Only allow deleting own messages or as admin
$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$messageId]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    echo json_encode(['success' => false, 'error' => 'Message not found']);
    exit;
}

if ($bothSides) {
    // Delete the message for everyone
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $success = $stmt->execute([$messageId]);
} else {
    // "Soft delete" for current user: you may want to implement a flag (e.g., deleted_by_sender/deleted_by_receiver)
    // For now, only allow sender to delete their own message
    if ($message['sender_id'] == $userId) {
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $success = $stmt->execute([$messageId]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
}

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
}
