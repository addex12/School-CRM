<?php
require_once '../../includes/auth.php';
require_once '../../includes/config.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$current_user_id = $_SESSION['user_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!$current_user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing receiver_id or message']);
    exit;
}

try {
    // Regular users can't send broadcasts
    if ($receiver_id === 'broadcast') {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Send to single user
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, sent_at, is_read) 
        VALUES (?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([$current_user_id, $receiver_id, $message]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}