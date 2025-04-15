<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$other_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$other_id || $other_id == $user_id) {
    echo json_encode([]);
    exit;
}
// Get messages between $user_id and $other_id, include sender username
$stmt = $pdo->prepare("SELECT m.*, u.username FROM chat_messages m JOIN users u ON m.from_user_id = u.id WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?) ORDER BY m.created_at ASC");
$stmt->execute([$user_id, $other_id, $other_id, $user_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
