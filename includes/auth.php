<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Prevent logged-in users from accessing the login page
if (basename($_SERVER['PHP_SELF']) === 'login.php' && isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['dashboard_path'] ?? '/index.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: login.php?error=csrf");
        exit();
    }

    // Sanitize inputs
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        // Fetch user with role information
        $stmt = $pdo->prepare("
            SELECT u.*, r.dashboard_path 
            FROM users u
            JOIN roles r ON u.role_id = r.id 
            WHERE (u.username = :username OR u.email = :username) AND u.active = 1
        ");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['dashboard_path'] = validate_dashboard_path($user['dashboard_path']); // Validate path

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);

            // Redirect to dashboard
            header("Location: " . $_SESSION['dashboard_path']);
            exit(); // Ensure no further code is executed
        } else {
            log_activity(null, 'login_failed', "Failed login attempt for username: $username");
            header("Location: login.php?error=invalid");
            exit(); // Ensure no further code is executed
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: login.php?error=system");
        exit(); // Ensure no further code is executed
    }
} else {
    header("Location: login.php");
    exit(); // Ensure no further code is executed
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit(); // Ensure no further code is executed
    }
}