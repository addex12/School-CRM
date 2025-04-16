<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $receiverId = $data['receiverId'] ?? null;
    $message = $data['message'] ?? '';

    if ($receiverId && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $receiverId, $message]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }
} catch (PDOException $e) {
    error_log("Error sending message: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}