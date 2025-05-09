<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();
require_once 'system_logs.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];

// Prevent deleting yourself (optional, but recommended)
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
    $_SESSION['error'] = "You cannot delete your own account.";
    header("Location: users.php");
    exit();
}

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found!";
    header("Location: users.php");
    exit();
}

// Delete user
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);

// Log the action
$_SESSION['success'] = "User deleted successfully.";
header("Location: users.php");
exit();
