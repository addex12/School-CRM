<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
session_start();

// Ensure $pdo is available
if (!isset($pdo) || !$pdo) {
    error_log('Database connection not available in logout.php');
}

// Log the logout action to audit_logs if user is logged in
if (isset($_SESSION['user_id']) && isset($pdo) && $pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['user_id'],
            'logout',
            'User logged out',
            htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown', ENT_QUOTES, 'UTF-8')
        ]);
    } catch (Exception $e) {
        error_log('Audit log insert failed (logout): ' . $e->getMessage());
    }

    // Set online=0 on logout
    try {
        $stmt = $pdo->prepare("UPDATE users SET online = 0 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log('Set user offline failed (logout): ' . $e->getMessage());
    }
}

// Destroy the session
session_unset();
session_destroy();
setcookie('remember_token', '', time() - 3600, '/');

// Redirect to login page
header("Location: login.php");
exit();
?>