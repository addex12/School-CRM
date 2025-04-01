<?php
require_once 'db.php';
require_once '../vendor/autoload.php'; // Ensure PHPMailer is loaded

// Register new user
function registerUser($username, $email, $password, $role = 'parent') {
    global $pdo;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $hashed_password, $role]);
    } catch (PDOException $e) {
        error_log("Error registering user: " . $e->getMessage());
        return false;
    }
}

// Login user
function loginUser($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return false;
    }
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Get all active surveys
function getActiveSurveys() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM surveys WHERE is_active = TRUE");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching active surveys: " . $e->getMessage());
        return [];
    }
}

// Get survey questions
function getSurveyQuestions($survey_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ?");
        $stmt->execute([$survey_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching survey questions: " . $e->getMessage());
        return [];
    }
}

// Apply system settings to the application
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit();
}

function formatDate($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}
if (!defined('DATETIMEFORMAT')) {
    define('DATETIMEFORMAT', 'Y-m-d H:i:s'); // Default datetime format
}

function formatDatetime($date, $format = DATETIMEFORMAT) {
    return date($format, strtotime($date));
}
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals);
}

function formatPhone($phone) {
    return preg_replace('/\D/', '', $phone);
}
function formatEmail($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}
function formatUrl($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}
function formatText($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
function formatHtml($html) {
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}
function formatJson($json) {
    return json_encode($json, JSON_PRETTY_PRINT);
}
function formatXml($xml) {
    return htmlspecialchars($xml, ENT_QUOTES, 'UTF-8');
}
function formatArray($array) {
    return implode(', ', $array);
}
function formatObject($object) {
    return json_encode($object, JSON_PRETTY_PRINT);
}
function formatBoolean($boolean) {
    return $boolean ? 'Yes' : 'No';
}
function formatCurrency($amount, $currency = 'USD') {
    return number_format($amount, 2) . ' ' . strtoupper($currency);
}
function formatPercentage($number, $decimals = 2) {
    return number_format($number, $decimals) . '%';
}
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}   
