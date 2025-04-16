<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE last_active > (NOW() - INTERVAL 5 MINUTE)");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (PDOException $e) {
    error_log("Error fetching online users: " . $e->getMessage());
    echo json_encode([]);
}