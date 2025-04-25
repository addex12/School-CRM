<?php
// track_activity.php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Enhanced security headers
header("Content-Security-Policy: default-src 'self'");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

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

// Enhanced data sanitization
$activityType = filter_var($data['type'] ?? 'unknown', FILTER_SANITIZE_SPECIAL_CHARS);
$timestamp = filter_var($data['timestamp'] ?? date('Y-m-d H:i:s'), FILTER_SANITIZE_SPECIAL_CHARS);

// Get user info if available
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'guest';

// Persistent tracking
$trackingId = $_COOKIE['persistent_tracking_id'] ?? null;
if (!$trackingId) {
    $trackingId = bin2hex(random_bytes(32));
    setcookie('persistent_tracking_id', $trackingId, [
        'expires' => time() + (86400 * 365 * 2),
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Enhanced device fingerprinting
$fingerprint = $data['fingerprint'] ?? null;
$deviceData = $data['device'] ?? [];

// Get IP with proxy consideration
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Prepare activity data with enhanced mobile tracking
$activityData = [
    'user_id' => $userId,
    'tracking_id' => $trackingId,
    'fingerprint' => $fingerprint,
    'action_type' => $activityType,
    'ip_address' => $ip,
    'user_agent' => $userAgent,
    'timestamp' => $timestamp,
    'device_data' => json_encode($deviceData),
    'details' => json_encode($data),
    'is_mobile' => $data['is_mobile'] ?? false,
    'app_foreground' => $data['app_state'] === 'foreground' ?? false,
    'battery_level' => $data['battery'] ?? null
];

try {
    // Insert into database with new mobile-specific fields
    $stmt = $pdo->prepare("INSERT INTO user_activity 
        (user_id, tracking_id, fingerprint, action_type, ip_address, user_agent, 
         timestamp, details, device_data, is_mobile, app_foreground, battery_level)
        VALUES (:user_id, :tracking_id, :fingerprint, :action_type, :ip_address, 
                :user_agent, :timestamp, :details, :device_data, :is_mobile, 
                :app_foreground, :battery_level)");
    
    $stmt->execute($activityData);
    
    // Enhanced logging
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/logs' . '.log';
    file_put_contents($logFile, json_encode($activityData) . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    http_response_code(200);
    echo 'OK';
} catch (PDOException $e) {
    error_log("Activity tracking error: " . $e->getMessage());
    http_response_code(500);
}
?>