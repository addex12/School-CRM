<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Validate authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['receiver_id']) || !isset($data['message']) || empty(trim($data['message']))) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Sanitize inputs
$sender_id = $_SESSION['user_id'];
$receiver_id = filter_var($data['receiver_id'], FILTER_VALIDATE_INT);
$message = htmlspecialchars(trim($data['message']));

// Save to database
try {
    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$sender_id, $receiver_id, $message]);
    
    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log('Database error: '.$e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Message sending failed']);
}