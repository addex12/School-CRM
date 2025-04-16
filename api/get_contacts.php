<?php
header('Content-Type: application/json');
require_once __DIR__.'/../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

if ($role_id == 1) {
    // Admin: get all users except self and admins
    $contacts = $pdo->query("SELECT id, username FROM users WHERE id != $current_user_id AND role_id != 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // User: get all admins
    $contacts = $pdo->query("SELECT id, username FROM users WHERE role_id = 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
}

// Get unread counts for each contact
$contact_ids = array_column($contacts, 'id');
$unread_counts = [];
if ($contact_ids) {
    $in = str_repeat('?,', count($contact_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT sender_id, COUNT(*) as unread FROM messages WHERE receiver_id = ? AND sender_id IN ($in) AND is_read = 0 GROUP BY sender_id");
    $stmt->execute(array_merge([$current_user_id], $contact_ids));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $unread_counts[$row['sender_id']] = $row['unread'];
    }
}
foreach ($contacts as &$c) {
    $c['unread'] = $unread_counts[$c['id']] ?? 0;
}
echo json_encode(['success' => true, 'contacts' => $contacts]);
