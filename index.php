<?php
// --- Security Features Start ---
// 1. Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}

// 2. Set Secure Headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; object-src 'none';");

// 3. Secure Session Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// 4. Regenerate session ID on login
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// 5. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 6. Rate Limiting for Login (example, should be in login.php)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_login_attempt'] = time();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (time() - $_SESSION['last_login_attempt'] < 60 && $_SESSION['login_attempts'] > 5) {
        die('Too many login attempts. Please wait a minute.');
    }
    $_SESSION['login_attempts']++;
    $_SESSION['last_login_attempt'] = time();
}

// 7. Hide PHP errors from users
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// 8. Input Sanitization Helper
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
$_GET = sanitize($_GET);
$_POST = sanitize($_POST);
$_COOKIE = sanitize($_COOKIE);

// 9. RBAC Enforcement Example (should be expanded in all scripts)
function require_role($role_id) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != $role_id) {
        header('Location: /login.php');
        exit();
    }
}
// --- Security Features End ---

// Redirect users coming from crm.flipperschools.com (typo) or crm.flipperschool.com to the new login page
if (
    isset($_SERVER['HTTP_HOST']) &&
    (
        strtolower($_SERVER['HTTP_HOST']) === 'crm.flipperschool.com'
    )
) {
    header('Location: https://crm.flipperschool.com/login.php');
    exit();
}

// Ensure the Auth class exists
if (!class_exists('Auth')) {
    class Auth {
        public static function isLoggedIn(): bool {
            return isset($_SESSION['user_id']);
        }
    }
}

// Include required files
require_once __DIR__ . '/includes/config.php'; // This must define $pdo
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Check if the user is logged in
if (!Auth::isLoggedIn()) {
    error_log("Redirecting to login.php because user is not logged in.");
    header("Location: login.php");
    exit();
}

// Get current user data
try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.role_id, u.last_active, u.online, u.active, u.avatar, r.role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Check if the user is active
    if (!$user['active']) {
        error_log("Inactive user attempted to log in: " . $user['username']);
        header("Location: inactive.php");
        exit();
    }

    // Update last active timestamp and online status
    $updateStmt = $pdo->prepare("UPDATE users SET last_active = NOW(), online = 1 WHERE id = ?");
    $updateStmt->execute([$user['id']]);
} catch (Exception $e) {
    error_log("User data fetch error: " . $e->getMessage());
    header("Location: error.php");
    exit();
}

// Redirect based on role_id
if ($user['role_id'] === 1) { // Admin role
    header("Location: admin/dashboard.php");
} else { // All other roles
    header("Location: user/dashboard.php");
}
exit();