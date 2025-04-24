<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $current_user_id = $_SESSION['user_id'] ?? null;
    if (!$current_user_id) {
        throw new Exception('Not authenticated');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $contact_id = $input['contact_id'] ?? ($_GET['user_id'] ?? null);

    if ($contact_id === null || $contact_id === '') {
        throw new Exception('Contact ID required');
    }

    if ($contact_id === 'broadcast') {
        if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
            throw new Exception('Permission denied');
        }
        $stmt = $pdo->prepare("
            SELECT m.*, u.username AS sender_username
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id IS NULL
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($messages as &$msg) {
            $msg['is_own'] = ($msg['sender_id'] == $current_user_id);
            $msg['sender'] = $msg['sender_username'];
            $msg['sent_at'] = $msg['sent_at'];
            $msg['message'] = $msg['content'];
        }
    } else {
        if (empty($current_user_id) || empty($contact_id)) {
            throw new Exception('User ID or Contact ID missing');
        }
        $params = [
            'current_user' => $current_user_id,
            'contact_id' => $contact_id
        ];
        $stmt = $pdo->prepare("
            SELECT m.*,
                   us.username AS sender_username,
                   ur.username AS receiver_username
            FROM messages m
            JOIN users us ON m.sender_id = us.id
            JOIN users ur ON m.receiver_id = ur.id
            WHERE (
                (m.sender_id = :current_user AND m.receiver_id = :contact_id AND m.deleted_by_sender = 0)
                OR
                (m.sender_id = :contact_id AND m.receiver_id = :current_user AND m.deleted_by_receiver = 0)
            )
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Only run this update for non-broadcast messages
        $update = $pdo->prepare("
            UPDATE messages SET is_read = 1
            WHERE receiver_id = :current_user AND sender_id = :contact_id
        ");
        $update->execute($params);

        foreach ($messages as &$msg) {
            $msg['is_own'] = ($msg['sender_id'] == $current_user_id);
            $msg['sender'] = $msg['sender_username'];
            $msg['sent_at'] = $msg['sent_at'];
            $msg['message'] = $msg['content'];
        }
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
