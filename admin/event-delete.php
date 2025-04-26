<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid event ID.";
    header("Location: events.php");
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Event deleted successfully.";
} catch (Exception $e) {
    error_log("Error deleting event: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting event.";
}

header("Location: events.php");
exit();
