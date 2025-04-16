<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Validate authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate input
// Support both JSON and form-urlencoded
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
    $data = $_POST;
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) $data = [];
}
if (!isset($data['receiver_id']) || !isset($data['message']) || empty(trim($data['message']))) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Sanitize inputs
$sender_id = $_SESSION['user_id'];
$message = htmlspecialchars(trim($data['message']));
$receiver_id_raw = $data['receiver_id'];

try {
    if ($receiver_id_raw === 'broadcast' && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        // Admin broadcast: send to all users (excluding admins and self)
        $users = $pdo->query("SELECT id FROM users WHERE role_id != 1 AND id != $sender_id")->fetchAll(PDO::FETCH_COLUMN);
        if (!$users) throw new Exception('No users found to broadcast');
        $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())');
        foreach ($users as $uid) {
            $stmt->execute([$sender_id, $uid, $message]);
        }
        echo json_encode(['success' => true, 'broadcast' => true, 'count' => count($users)]);
    } else {
        $receiver_id = filter_var($receiver_id_raw, FILTER_VALIDATE_INT);
        if (!$receiver_id) throw new Exception('Invalid receiver');
        $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$sender_id, $receiver_id, $message]);
        echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
    }
} catch (Exception $e) {
    error_log('Message send error: '.$e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}