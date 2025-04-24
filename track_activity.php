<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Log all activity regardless of login status (but still record user info if available)
$user_id = $_SESSION['user_id'] ?? 'guest';
$logged_in = $_SESSION['logged_in'] ?? false;
$username = $_SESSION['username'] ?? 'guest';
$role = $_SESSION['role_id'] ?? 'guest';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    exit('No data');
}

// Enhanced data collection with additional fields
$action = $data['action'] ?? 'unknown';
$element = $data['tag'] ?? '';
$element_id = $data['id'] ?? '';
$element_class = $data['class'] ?? '';
$text = $data['text'] ?? '';
$value = $data['value'] ?? '';
$href = $data['href'] ?? '';
$page = $data['page'] ?? $_SERVER['REQUEST_URI'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$timestamp = date('Y-m-d H:i:s');
$created_at = $timestamp;

// Insert into activity_logs table
try {
    $stmt = $pdo->prepare("INSERT INTO activity_logs 
        (user_id, username, role, action, element, element_id, element_class, text, value, href, page, ip_address, user_agent, timestamp, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id, $username, $role, $action, $element, $element_id, $element_class, 
        $text, $value, $href, $page, $ip, $user_agent, $timestamp, $created_at
    ]);
} catch (PDOException $e) {
    error_log("Activity log insert failed: " . $e->getMessage());
}

// Enhanced log file entry
$logLine = json_encode([
    'user_id' => $user_id,
    'username' => $username,
    'role' => $role,
    'action' => $action,
    'element' => $element,
    'element_id' => $element_id,
    'element_class' => $element_class,
    'text' => $text,
    'value' => $value,
    'href' => $href,
    'page' => $page,
    'ip_address' => $ip,
    'user_agent' => $user_agent,
    'timestamp' => $timestamp,
    'created_at' => $created_at
]) . PHP_EOL;

// Ensure log directory exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

file_put_contents($logDir . '/activity.log', $logLine, FILE_APPEND);

echo 'ok';