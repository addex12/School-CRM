<?php 
function requireAdmin() {
    session_start();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        if (basename($_SERVER['PHP_SELF']) !== 'error.php') { // Prevent redirect loop
            $_SESSION['error'] = "Access denied. Admins only.";
            header("Location: ../error.php");
            exit();
        }
    }
}