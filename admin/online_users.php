<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

// Return all users except admin, with online status
$stmt = $pdo->prepare("SELECT id, username, fullname, (last_active > (NOW() - INTERVAL 5 MINUTE)) as online FROM users WHERE role != 'admin'");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
