<?php
session_start();

// Replace with your actual checking logic...
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Replace with your actual DB connection...
$conn = new mysqli("localhost", "username", "password", "SchoolCRM_DB");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
