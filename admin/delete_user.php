<?php
session_start();

// Replace with your actual checking logic...
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/db.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    header('Location: dashboard.php');
    exit();
} else {
    echo "Error deleting user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
