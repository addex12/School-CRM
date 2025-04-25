<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Security headers
header("Content-Security-Policy: default-src 'self'");
header("X-Content-Type-Options: nosniff");

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get raw input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    exit;
}

// Basic data sanitization
$activityType = filter_var($data['type'] ?? 'unknown', FILTER_SANITIZE_SPECIAL_CHARS);
$timestamp = filter_var($data['timestamp'] ?? date('Y-m-d H:i:s'), FILTER_SANITIZE_SPECIAL_CHARS);

// Get user info if available
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'guest';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Prepare activity data
$activityData = [
    'user_id' => $userId,
    'action' => $activityType, // Map to 'action' column
    'element' => $data['element'] ?? 'unknown', // Assuming 'element' is part of the input data
    'details' => json_encode($data),
    'ip_address' => $ip,
    'created_at' => $timestamp
];

try {
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO user_activity 
        (user_id, action, element, details, ip_address, created_at)
        VALUES (:user_id, :action, :element, :details, :ip_address, :created_at)");
    
    $stmt->execute($activityData);
    
    // Also log to file for redundancy
    $logDir = __DIR__ . '/logs/activity';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/' . date('Y-m-d') . '.log';
    file_put_contents($logFile, json_encode($activityData) . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    http_response_code(200);
    echo 'OK';
} catch (PDOException $e) {
    error_log("Activity tracking error: " . $e->getMessage());
    http_response_code(500);
}
?>