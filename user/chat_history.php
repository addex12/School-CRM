<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$admin_id = 1; // Change if your admin user_id is different
$stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?) ORDER BY created_at ASC");
$stmt->execute([$user_id, $admin_id, $admin_id, $user_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
