<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$current_user_id = $_SESSION['sender_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;
$content = trim($_POST['content'] ?? '');

if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!$receiver_id || !$content) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing receiver_id or content']);
    exit;
}

try {
    if ($receiver_id === 'broadcast') {
        // Send to all non-admin users
        $stmt = $pdo->query("SELECT id FROM users WHERE role_id != 1");
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$user_ids) {
            echo json_encode(['success' => false, 'error' => 'No users to broadcast to']);
            exit;
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, sent_at, is_read) 
                              VALUES (?, ?, ?, NOW(), 0)");

        foreach ($user_ids as $uid) {
            // Optionally skip sending to yourself
            if ($uid == $current_user_id) continue;
            $stmt->execute([$current_user_id, $uid, $content]);
        }

        $pdo->commit();
    } else {
        // Ensure receiver_id is an integer
        $receiver_id = (int)$receiver_id;
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, sent_at, is_read) 
                              VALUES (?, ?, ?, NOW(), 0)");
        $stmt->execute([$current_user_id, $receiver_id, $content]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}