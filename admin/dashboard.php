<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
$pageTitle = "Admin Dashboard";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Fetch widget data
$widgets = [
    [
        "title" => "Total Users",
        "icon" => "fa-users",
        "color" => "blue",
        "query" => "SELECT COUNT(*) FROM users"
    ],
    [
        "title" => "Active Surveys",
        "icon" => "fa-poll",
        "color" => "green",
        "query" => "SELECT COUNT(*) FROM surveys WHERE is_active = 1"
    ],
    [
        "title" => "Feedback Received",
        "icon" => "fa-comments",
        "color" => "orange",
        "query" => "SELECT COUNT(*) FROM feedback"
    ],
    [
        "title" => "Open Tickets",
        "icon" => "fa-ticket-alt",
        "color" => "red",
        "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'"
    ]
];

foreach ($widgets as &$widget) {
    try {
        $stmt = $pdo->query($widget['query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        $widget['count'] = "Error";
        error_log("Widget Error: " . $e->getMessage());
    }
}

// Fetch recent activity log
$activityLog = [];
try {
    $stmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
    $activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Activity Log Error: " . $e->getMessage());
}

// Fetch recent feedback
$feedback = [];
try {
    $stmt = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 5");
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Feedback Error: " . $e->getMessage());
}

// Fetch recent support tickets
$tickets = [];
try {
    $stmt = $pdo->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 5");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Tickets Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <!-- Widgets Section -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3><?= htmlspecialchars($widget['count']) ?></h3>
                            <p><?= htmlspecialchars($widget['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Activity Log Section -->
                <div class="dashboard-section">
                    <h2>Recent Activity Log</h2>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Activity Type</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activityLog)): ?>
                                    <?php foreach ($activityLog as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['id']) ?></td>
                                            <td><?= htmlspecialchars($log['user_id']) ?></td>
                                            <td><?= htmlspecialchars($log['activity_type']) ?></td>
                                            <td><?= htmlspecialchars($log['description']) ?></td>
                                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No recent activity found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feedback Section -->
                <div class="dashboard-section">
                    <h2>Recent Feedback</h2>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Rating</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($feedback)): ?>
                                    <?php foreach ($feedback as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['id']) ?></td>
                                            <td><?= htmlspecialchars($item['user_id']) ?></td>
                                            <td><?= htmlspecialchars($item['subject']) ?></td>
                                            <td><?= htmlspecialchars($item['message']) ?></td>
                                            <td><?= htmlspecialchars($item['rating']) ?></td>
                                            <td><?= htmlspecialchars($item['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No feedback found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Support Tickets Section -->
                <div class="dashboard-section">
                    <h2>Recent Support Tickets</h2>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tickets)): ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ticket['id']) ?></td>
                                            <td><?= htmlspecialchars($ticket['user_id']) ?></td>
                                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                            <td><?= htmlspecialchars($ticket['status']) ?></td>
                                            <td><?= htmlspecialchars($ticket['priority']) ?></td>
                                            <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No tickets found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>