<?php
ob_start(); // Start output buffering
// Ensure no output before this point
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
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
        global $pdo;
        if (!isLoggedIn()) {
            return null;
        }
        try {
            $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, r.role_name 
                                 FROM users u 
                                 JOIN roles r ON u.role_id = r.id 
                                 WHERE u.id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getCurrentUser: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isset($_SESSION['role_id']) || intval($_SESSION['role_id']) !== 1) {
            $_SESSION['error'] = "Access denied. Admins only.";
            header("Location: ../error.php");
            exit();
        }
    }
}

if (!function_exists('setUserSession')) {
    function setUserSession(int $user_id): bool {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error in setUserSession: " . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('logout')) {
    function logout() {
        session_unset();
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
}
if (!function_exists('isCsrfTokenValid')) {
    function isCsrfTokenValid($token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('validateCsrfToken')) {
    function validateCsrfToken($token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
if (!function_exists('safe_json_decode')) {
    function safe_json_decode($json): array {
        return $json ? json_decode($json, true) : [];
    }
}
if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole(): ?string {
        return isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
    }
}
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId(): ?int {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
}
if (!function_exists('getCurrentUserName')) {
    function getCurrentUserName(): ?string {
        return isset($_SESSION['username']) ? $_SESSION['username'] : null;
    }
}
if (!function_exists('getCurrentUserEmail')) {
    function getCurrentUserEmail(): ?string {
        return isset($_SESSION['email']) ? $_SESSION['email'] : null;
    }
}
if (!function_exists('getCurrentUserFullName')) {
    function getCurrentUserFullName(): ?string {
        return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : null;
    }
}
if (!function_exists('getCurrentUserProfilePicture')) {
    function getCurrentUserProfilePicture(): ?string {
        return isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : null;
    }
}
 ob_end_flush(); // End output buffering and flush the output