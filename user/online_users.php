<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
// Get all users except self, show online status (last_active within 5 min)
$stmt = $pdo->prepare("SELECT id, username, fullname, (last_active > (NOW() - INTERVAL 5 MINUTE)) as online FROM users WHERE id != ?");
$stmt->execute([$user_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
