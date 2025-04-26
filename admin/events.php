<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!$pdo) {
    error_log("Database connection not established.");
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
} else {
    error_log("Database connection established successfully.");
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

include 'includes/admin_sidebar.php';
?>

<div class="admin-main">
    <header class="admin-header" style="display: flex; align-items: center; justify-content: space-between;">
        <h1 style="margin: 0;"><?= htmlspecialchars($pageTitle) ?></h1>
        <a href="event-create.php" class="erpnext-btn btn-primary">
            <i class="fas fa-plus"></i> Create New Event
        </a>
    </header>

    <div class="content">
        <!-- Events Table Section -->
        <div class="dashboard-section">
            <h2>All Events</h2>
            <div class="table-container">
                <?php if (!empty($events)): ?>
                    <table>
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
                                        <a href="event-edit.php?id=<?= $event['id'] ?>" class="erpnext-btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="event-delete.php?id=<?= $event['id'] ?>" class="erpnext-btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this event?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
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

        <!-- Recent Events Section -->
        <div class="dashboard-section">
            <h2>Recent Events</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($events)): ?>
                            <?php foreach (array_slice($events, 0, 5) as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['title']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($event['start_date'])) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($event['end_date'])) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($event['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No recent events found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
