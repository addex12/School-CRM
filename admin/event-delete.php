<?php
include('../config.php'); // Include database connection

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];

    $delete_query = "DELETE FROM events WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $event_id);

    if ($stmt->execute()) {
        header("Location: events.php?message=Event deleted successfully");
        exit();
    } else {
        echo "Error deleting event.";
    }
} else {
    header("Location: events.php?message=Invalid event ID");
    exit();
}
?>
