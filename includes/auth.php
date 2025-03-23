<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 */
session_start();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/functions.php';

function login($username, $password) {
    global $conn;
    $username = sanitize($username);
    $password = sanitize($password);

    $sql = "SELECT * FROM parents WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['parent_id'] = $row['parent_id'];
            $_SESSION['username'] = $row['username'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['parent_id']);
}

function logout() {
    session_destroy();
    redirect('index.php');
}

if (isset($_GET['logout'])) {
    logout();
}
?>