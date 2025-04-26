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

<style>
    .admin-main {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: #f8f9fa;
        font-family: Arial, sans-serif;
    }

    .admin-header {
        background-color: #007bff;
        color: white;
        padding: 15px;
        border-radius: 5px;
    }

    .admin-header h1 {
        font-size: 24px;
    }

    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        color: white;
        font-size: 14px;
        cursor: pointer;
    }

    .btn-primary {
        background-color: #007bff;
    }

    .btn-secondary {
        background-color: #6c757d;
    }

    .btn-danger {
        background-color: #dc3545;
    }

    .dashboard-section {
        margin: 20px 0;
    }

    .card {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .table th, .table td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }

    .table th {
        background-color: #f1f1f1;
    }

    .search-bar {
        margin-bottom: 15px;
        display: flex;
        justify-content: flex-end;
    }

    .search-bar input {
        padding: 8px;
        width: 300px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
</style>

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
                <?php if (!empty($events)): ?>
                    <table class="table table-bordered" id="eventsTable">
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
                                        <a href="event-edit.php?id=<?= $event['id'] ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="event-delete.php?id=<?= $event['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this event?');">
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

<?php include 'includes/footer.php'; ?>
