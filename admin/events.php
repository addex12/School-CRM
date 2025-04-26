<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!class_exists('Auth')) {
    class Auth {
        public static function isAdmin(): bool {
            return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
        }
    }
}

if (!Auth::isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Manage Events";

// Fetch all events from the database
try {
    $stmt = $pdo->prepare("SELECT id, title, start_date, end_date, created_at FROM events ORDER BY start_date ASC");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
    $events = [];
}

include __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="admin-main">
    <div class="admin-container">
        <div class="page-header">
            <h1>Manage Events</h1>
            <a href="event-create.php" class="btn btn-primary">Create New Event</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Events</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($events)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['title']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($event['start_date'])) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($event['end_date'])) ?></td>
                                    <td>
                                        <a href="event-edit.php?id=<?= $event['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="event-delete.php?id=<?= $event['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No events found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
