<?php
// Start output buffering to prevent unintended output
if (session_status() === PHP_SESSION_NONE) {
    ob_start();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header("Location: ../login.php");
            exit();
        }
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser(): ?array {
        global $pdo; // Ensure $pdo is accessible
        if (!isLoggedIn()) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, r.role_name 
                               FROM users u 
                               JOIN roles r ON u.role_id = r.id 
                               WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
            header("Location: /user/dashboard.php");
            exit();
        }
    }
}
// Ensure $user_id is set and valid
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("Location: /login.php");
    exit();
}

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: /login.php");
    exit();
}

// Access user details safely
$user_role = $user['role'] ?? null;
$user_email = $user['email'] ?? null;

// After successful login
$stmt = $pdo->prepare("
    SELECT users.*, roles.role_name 
    FROM users 
    JOIN roles ON users.role_id = roles.id 
    WHERE users.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$_SESSION['role_id'] = $user['role_id'];
$_SESSION['role'] = $user['role_name'];