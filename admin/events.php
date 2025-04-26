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
    $stmt = $pdo->prepare("SELECT id, title, start_date, end_date, description, created_at FROM events ORDER BY start_date ASC");
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
        <a href="event-create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Event
        </a>
    </header>

    <div class="content">
        <!-- Events Table Section -->
        <div class="dashboard-section">
            <h2>All Events</h2>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search events..." onkeyup="filterTable()">
            </div>
            <div class="card">
                <div class="events-list">
                    <h2 style="font-size: 1.5rem; margin-bottom: 16px;">All Events</h2>
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                            <div class="event-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                                <h3 style="font-size: 1.25rem; margin-bottom: 8px;"><?= htmlspecialchars($event['title']) ?></h3>
                                <small style="font-size: 0.9rem; color: #555;">
                                    <?= date('M j, Y g:i A', strtotime($event['start_date'])) ?> 
                                    to <?= date('M j, Y g:i A', strtotime($event['end_date'])) ?>
                                </small>
                                <p style="font-size: 0.9rem; color: #555; margin-top: 8px;"><?= nl2br(htmlspecialchars($event['description']) ?? '') ?></p>
                                <p style="font-size: 0.9rem; color: #555;">Created At: <?= date('M j, Y g:i A', strtotime($event['created_at'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="font-size: 0.9rem; color: #555;">No events found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Events Section -->
        <div class="dashboard-section">
            <h2>Recent Events</h2>
            <div class="card">
                <table class="table table-bordered">
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
                        </div>
<?php require_once 'includes/footer.php'; ?>

<script>
    function filterTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('eventsTable');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let match = false;

            for (let j = 0; j < cells.length; j++) {
                if (cells[j].innerText.toLowerCase().includes(filter)) {
                    match = true;
                    break;
                }
            }

            rows[i].style.display = match ? '' : 'none';
        }
    }
</script>
