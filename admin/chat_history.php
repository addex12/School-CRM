<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?) ORDER BY created_at ASC");
$stmt->execute([$admin_id, $user_id, $user_id, $admin_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
