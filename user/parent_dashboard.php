<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$announcements = $conn->query("SELECT title, content, created_at FROM announcements ORDER BY created_at DESC");
$events = $conn->query("SELECT title, event_date FROM events ORDER BY event_date DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parent Dashboard</title>
</head>
<body>
    <h1>Parent Dashboard</h1>
    <section id="announcements">
        <h2>Announcements</h2>
        <?php while ($announcement = $announcements->fetch_assoc()): ?>
            <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
            <p><?php echo htmlspecialchars($announcement['content']); ?></p>
            <p><small><?php echo $announcement['created_at']; ?></small></p>
        <?php endwhile; ?>
    </section>

    <section id="events">
        <h2>Events</h2>
        <?php while ($event = $events->fetch_assoc()): ?>
            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
            <p><small><?php echo $event['event_date']; ?></small></p>
        <?php endwhile; ?>
    </section>
</body>
</html>
