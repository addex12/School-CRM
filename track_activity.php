<?php
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

// Rate limiting (1 request per second per IP)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$cacheKey = 'rate_limit_' . md5($ip);
$lastRequest = $_SESSION[$cacheKey] ?? null;

if ($lastRequest && (time() - $lastRequest) < 1) {
    http_response_code(429);
    exit;
}
$_SESSION[$cacheKey] = time();

// Get and validate input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    exit;
}

// Enhanced data sanitization
$activityType = filter_var($data['action'], FILTER_SANITIZE_SPECIAL_CHARS);
$timestamp = filter_var($data['timestamp'] ?? date('Y-m-d H:i:s'), FILTER_SANITIZE_SPECIAL_CHARS);

// Get user info if available
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'guest';

// Persistent tracking with enhanced device identification
$trackingId = $_COOKIE['student_tracker_id'] ?? null;
if (!$trackingId) {
    $trackingId = 'st_' . bin2hex(random_bytes(12));
    setcookie('student_tracker_id', $trackingId, time() + (86400 * 365 * 2), '/', '', true, true);
}

// Enhanced device fingerprinting
$fingerprint = $data['fingerprint'] ?? null;
$deviceType = 'desktop'; // Default

// Detect mobile devices
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
if (preg_match('/(android|iphone|ipad|mobile)/i', $userAgent)) {
    $deviceType = 'mobile';
    
    // Capture additional mobile-specific data if available
    if (isset($data['sensor'])) {
        $data['mobile_sensors'] = [
            'accelerometer' => $data['sensor']['accel'] ?? null,
            'gyroscope' => $data['sensor']['gyro'] ?? null,
            'orientation' => $data['sensor']['orientation'] ?? null,
            'battery' => $data['sensor']['battery'] ?? null
        ];
    }
}

// Prepare enhanced activity data
$activityData = [
    'user_id' => $userId,
    'username' => $username,
    'tracking_id' => $trackingId,
    'fingerprint' => $fingerprint,
    'device_type' => $deviceType,
    'action_type' => $activityType,
    'ip_address' => $ip,
    'user_agent' => $userAgent,
    'timestamp' => $timestamp,
    'details' => json_encode($data),
    'location_data' => null
];

// Add geolocation if available (for mobile devices)
if (isset($data['geolocation'])) {
    $activityData['location_data'] = json_encode([
        'latitude' => filter_var($data['geolocation']['lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        'longitude' => filter_var($data['geolocation']['lon'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        'accuracy' => filter_var($data['geolocation']['accuracy'], FILTER_SANITIZE_NUMBER_FLOAT)
    ]);
}

try {
    // Insert into database with enhanced schema
    $stmt = $pdo->prepare("INSERT INTO student_activity_monitor 
        (user_id, username, tracking_id, fingerprint, device_type, action_type, 
         ip_address, user_agent, timestamp, details, location_data, created_at)
        VALUES (:user_id, :username, :tracking_id, :fingerprint, :device_type, :action_type, 
                :ip_address, :user_agent, :timestamp, :details, :location_data, NOW())");
    
    $stmt->execute($activityData);
    
    // Log to file with rotation
    $logDir = __DIR__ . '/logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Rotate logs daily
    $logFile = $logDir . 'logs' . '.log';
    file_put_contents($logFile, json_encode($activityData) . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    http_response_code(200);
    echo 'OK';
} catch (PDOException $e) {
    error_log("Student tracking error: " . $e->getMessage());
    http_response_code(500);
}
?>