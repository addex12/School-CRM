<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Ensure session is active and user is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
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
$action = $data['action'] ?? 'click'; // Allow JS to send custom action, fallback to 'click'
$element = $data['tag'] ?? '';
$element_id = $data['id'] ?? '';
$element_class = $data['class'] ?? '';
$text = $data['text'] ?? '';
$href = $data['href'] ?? '';
$page = $data['page'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$timestamp = date('Y-m-d H:i:s');
$created_at = $timestamp;

// Insert into activity_logs table (with created_at)
$stmt = $pdo->prepare("INSERT INTO activity_logs 
    (user_id, username, role, action, element, element_id, element_class, text, href, page, ip_address, timestamp, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $user_id, $username, $role, $action, $element, $element_id, $element_class, $text, $href, $page, $ip, $timestamp, $created_at
]);

// Also log to activity_log file
$logLine = json_encode([
    'user_id' => $user_id,
    'username' => $username,
    'role' => $role,
    'action' => $action,
    'element' => $element,
    'element_id' => $element_id,
    'element_class' => $element_class,
    'text' => $text,
    'href' => $href,
    'page' => $page,
    'ip_address' => $ip,
    'timestamp' => $timestamp,
    'created_at' => $created_at
]) . PHP_EOL;

file_put_contents(__DIR__ . '/activity_log', $logLine, FILE_APPEND);

echo 'ok';
