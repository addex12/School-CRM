<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $contacts = [];
    // Add broadcast option as the first contact
    $contacts[] = [
        'id' => 'broadcast',
        'username' => 'Broadcast to All Users',
        'unread' => 0
    ];

    // Exclude admin users (role_id = 1)
    $stmt = $pdo->query("SELECT id, username FROM users WHERE role_id != 1 ORDER BY username");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contacts[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'unread' => 0 // Set to 0 or fetch actual unread count if available
        ];
    }
    echo json_encode(['success' => true, 'contacts' => $contacts]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
