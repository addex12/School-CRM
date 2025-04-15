<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

// Show users with last_active in last 5 min (except admin)
$stmt = $pdo->prepare("SELECT id, username, fullname FROM users WHERE last_active > (NOW() - INTERVAL 5 MINUTE) AND role != 'admin'");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
