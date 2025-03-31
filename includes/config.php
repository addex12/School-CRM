<?php
// Add at the top of includes/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer autoloader
require_once __DIR__ . '/db.php'; // Ensure the correct path to the db.php file
require_once __DIR__ . '/functions.php'; // Ensure the correct path to the functions.php file

// Database connection


// Base configuration
define(constant_name: 'BASE_URL', value: 'https://crm.flipperschool.com');
define(constant_name: 'UPLOAD_DIR', value: __DIR__ . '/../uploads');

// OpenAI API Key
define('OPENAI_API_KEY', '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'); // Replace with your actual API key

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

