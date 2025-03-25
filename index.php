<?php
require_once 'includes/auth.php';
require_once 'includes/config.php'; // Ensure this initializes $pdo
// Redirect based on login status
if ($auth->isLoggedIn()) {
    header("Location: " . ($auth->getUser()['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
}else {
        header("Location: login.php");
}
exit();
