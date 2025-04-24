<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$messageId = (int)$data['id'];
$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
$bothSides = !empty($data['both_sides']);

// Fetch message
$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$messageId]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Message not found']);
    exit;
}

// Soft delete logic: set deleted_by_sender or deleted_by_receiver
if ($bothSides) {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    // Admin: delete for both sides (hard delete)
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $success = $stmt->execute([$messageId]);
} else {
    // Only sender or receiver can soft delete for themselves
    if ($message['sender_id'] == $userId) {
        // Mark as deleted by sender
        $stmt = $pdo->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?");
        $success = $stmt->execute([$messageId]);
    } elseif ($message['receiver_id'] == $userId) {
        // Mark as deleted by receiver
        $stmt = $pdo->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?");
        $success = $stmt->execute([$messageId]);
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    // Optionally, hard delete if both have deleted
    if ($success) {
        $stmt = $pdo->prepare("SELECT deleted_by_sender, deleted_by_receiver FROM messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $flags = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($flags && $flags['deleted_by_sender'] && $flags['deleted_by_receiver']) {
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->execute([$messageId]);
        }
    }
}

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
}
