<?php
session_start();
require_once '../config/database.php';

function login($username, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function register($username, $password, $role) {
    global $conn;
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $hashed_password, $role);
    return $stmt->execute();
}

function updateUserRole($userId, $newRole) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param('si', $newRole, $userId);
    return $stmt->execute();
}

function sendEmail($to, $subject, $message) {
    $headers = 'From: no-reply@technobros.com' . "\r\n" .
               'Reply-To: no-reply@technobros.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    return mail($to, $subject, $message, $headers);
}
?>
