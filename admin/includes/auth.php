<?php

function requireAdmin() {
    session_start();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['error'] = "Access denied. Admins only.";
        header("Location: ../error.php");
        exit();
    }
}