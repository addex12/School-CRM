<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    header("Location: events.php?error=Invalid event ID");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    header("Location: events.php?message=Event deleted successfully");
    exit();
} catch (Exception $e) {
    error_log("Error deleting event: " . $e->getMessage());
    header("Location: events.php?error=Failed to delete event");
    exit();
}
?>
