<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer autoloader
require_once __DIR__ . '/db.php'; // Ensure the correct path to the db.php file
require_once __DIR__ . '/functions.php'; // Ensure the correct path to the functions.php file

// Base configuration
define('BASE_URL', 'http:/crm.flipperschool.com/');
define('UPLOAD_DIR', __DIR__ . '/../uploads');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_crm');
define('DB_USER', 'flipperschool');
define('DB_PASS', '');

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function safe_json_decode($json) {
    return $json ? json_decode($json, true) : [];
}

// Generate CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
