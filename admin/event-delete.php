<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $eventId = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
        $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success'] = "Event deleted successfully.";
        header("Location: events.php");
        exit();
    } catch (Exception $e) {
        error_log("Error deleting event: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting event.";
    }
} else {
    header("Location: events.php");
    exit();
}
?>
