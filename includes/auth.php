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
                // Always set user_role for admin checks
                if ($user['role_id'] == 1) {
                    $_SESSION['user_role'] = 'admin';
                } else {
                    $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
                    $roleStmt->execute([$user['role_id']]);
                    $_SESSION['user_role'] = $roleStmt->fetchColumn() ?: '';
                }
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error in setUserSession: " . $e->getMessage());
            return false;
        }
    }
}
ob_end_flush(); // Flush the output buffer