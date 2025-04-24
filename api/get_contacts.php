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
    
    $contacts = [];
    $isAdmin = ($_SESSION['role_id'] ?? 0) == 1;
    
    // Add broadcast option for admins
    if ($isAdmin) {
        $contacts[] = [
            'id' => 'broadcast',
            'username' => 'Broadcast to All Users',
            'unread' => 0
        ];
    }
    
    // Get all users with unread counts
    $query = "
        SELECT u.id, u.username, 
               COUNT(m.id) as unread
        FROM users u
        LEFT JOIN messages m ON m.sender_id = u.id 
            AND m.receiver_id = :current_user 
            AND m.is_read = 0
            AND m.deleted_by_receiver = 0
        WHERE u.id != :current_user
        GROUP BY u.id, u.username
        ORDER BY u.username
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['current_user' => $current_user_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contacts[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'unread' => (int)$row['unread']
        ];
    }
    
    echo json_encode(['success' => true, 'contacts' => $contacts]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}