<?php
// filepath: /home/orbalia/School-CRM/index.php

// Start the session
session_start();

// Include the file where the Auth class is defined
require_once 'includes/auth.php';

// Ensure the Auth class exists
if (!class_exists('Auth')) {
    class Auth {
        public static function isLoggedIn(): bool {
            return isset($_SESSION['user_id']);
        }
    }
}

// Check if the user is logged in
if (!Auth::isLoggedIn()) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// If logged in, display the dashboard or main content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Welcome to the School CRM</h1>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</p>
        <p>This is your dashboard where you can manage your account and view important information.</p>
    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> School CRM. All rights reserved.</p>
    </footer>
</body>
</html>