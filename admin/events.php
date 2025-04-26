<?php
session_start();
require_once __DIR__ . '/../includes/config.php';


if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Manage Events";

// Fetch events from the database
try {
    $stmt = $pdo->prepare("SELECT id, title, start_date, end_date, created_at FROM events ORDER BY start_date ASC");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
    $events = [];
}

include 'includes/header.php';
?>

<main>
    <h1>Manage Events</h1>
    <a href="event-create.php" class="btn btn-primary">Create New Event</a>
    <table border="1" style="width: 100%; margin-top: 20px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['id']) ?></td>
                        <td><?= htmlspecialchars($event['title']) ?></td>
                        <td><?= date('M j, Y g:i A', strtotime($event['start_date'])) ?></td>
                        <td><?= date('M j, Y g:i A', strtotime($event['end_date'])) ?></td>
                        <td>
                            <a href="event-edit.php?id=<?= $event['id'] ?>">Edit</a>
                            <a href="event-delete.php?id=<?= $event['id'] ?>" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No events found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<?php include 'includes/footer.php'; ?>
