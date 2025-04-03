<?php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role_id'] != 1) {
        header("Location: /user/dashboard.php");
        exit();
    }
}
?>
