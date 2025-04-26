<?php
// Start the session
session_start();

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
        SELECT u.id, u.username, u.role_id, r.role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    error_log("User data fetch error: " . $e->getMessage());
    header("Location: error.php");
    exit();
}

// Redirect based on role_id
switch ($user['role_id']) {
    case 1: // Admin role
        header("Location: admin/dashboard.php");
        break;
    case 2: // Teacher role
        header("Location: teacher/dashboard.php");
        break;
    case 3: // Student role
        header("Location: student/dashboard.php");
        break;
    case 4: // Parent role
        header("Location: parent/dashboard.php");
        break;
    default: // Unknown role
        header("Location: error.php");
        break;
}
exit();