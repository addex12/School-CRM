<?php
header('Content-Type: application/json');
require_once __DIR__.'/../config.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$current_user_id = $_SESSION['user_id'];
$other_user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
if (!$other_user_id) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id']);
    exit;
}
try {
    $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
    $stmt->execute([$other_user_id, $current_user_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Mark read error: '.$e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to mark as read']);
}
