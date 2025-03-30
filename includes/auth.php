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
?>
