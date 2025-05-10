<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 * Patent rights reserved.
 */
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Debugging: Check session variables
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Log session data for debugging (remove in production)
    $safeSession = array_map(function($k, $v) {
        $sensitive = ['password', 'token', 'auth', 'csrf'];
        foreach ($sensitive as $word) {
            if (stripos($k, $word) !== false) return '[MASKED]';
        }
        return is_scalar($v) ? htmlspecialchars((string)$v) : '[COMPLEX]';
    }, array_keys($_SESSION), $_SESSION);
    error_log("Access denied. Session data: " . print_r($safeSession, true));
    header("Location: login.php");
    exit;
}

// Check if an error message is set in the session
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
} else {
    // If no error message is set, redirect to the home page
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="error-container">
        <h1>Error</h1>
        <p><?= htmlspecialchars($error_message) ?></p>
        <a href="index.php" class="btn">Go Back to Home</a>
    </div>
</body>
</html>
