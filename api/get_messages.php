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
    $contact_id = $input['contact_id'] ?? null;

    if (!$contact_id) {
        throw new Exception('Contact ID required');
    }

    // Broadcast: admin sees all messages sent as broadcast
    if ($contact_id === 'broadcast') {
        if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
            throw new Exception('Permission denied');
        }
        $stmt = $pdo->prepare("
            SELECT m.*, u.username AS sender_username
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id IS NULL
            ORDER BY m.created_at ASC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Normal conversation: fetch messages between current user and contact, respecting soft delete
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
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([
            'current_user' => $current_user_id,
            'contact_id' => $contact_id
        ]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark messages as read where current user is receiver
        $update = $pdo->prepare("
            UPDATE messages SET is_read = 1
            WHERE receiver_id = :current_user AND sender_id = :contact_id
        ");
        $update->execute([
            'current_user' => $current_user_id,
            'contact_id' => $contact_id
        ]);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
