<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Not logged in');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    exit('No data');
}

// Prepare data for logging
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role_id'] ?? '';
$action = 'click';
$element = $data['tag'] ?? '';
$element_id = $data['id'] ?? '';
$element_class = $data['class'] ?? '';
$text = $data['text'] ?? '';
$href = $data['href'] ?? '';
$page = $data['page'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$timestamp = date('Y-m-d H:i:s');

// Insert into activity_logs
$stmt = $pdo->prepare("INSERT INTO activity_logs 
    (user_id, username, role, action, element, element_id, element_class, text, href, page, ip_address, timestamp)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $user_id, $username, $role, $action, $element, $element_id, $element_class, $text, $href, $page, $ip, $timestamp
]);

echo 'ok';
