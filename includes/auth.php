<?php
// includes/auth.php
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

//-
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {//-
    if (!isLoggedIn()) {//+
        header("Location: ../login.php");
        exit();
    }
}

function getCurrentUser(): ?array {
    return isLoggedIn() ? [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ] : null;
}
//+
function requireAdmin() {
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
        header("Location: /user/dashboard.php");
        exit();
    }
}
}
?>
