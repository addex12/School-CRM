<?php
// Add at the top of includes/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer autoloader
require_once 'db.php';

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Base configuration
define(constant_name: 'BASE_URL', value: 'https://crm.flipperschool.com');
define(constant_name: 'UPLOAD_DIR', value: __DIR__ . '/../uploads');

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
