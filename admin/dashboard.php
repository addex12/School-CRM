<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// Fetch statistics
$stats = [
    'Total Users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'Active Surveys' => $pdo->query("SELECT COUNT(*) FROM surveys WHERE is_active = 1")->fetchColumn(),
    'Total Responses' => $pdo->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn(),
    'Open Tickets' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn(),
];

// Fetch recent tickets
$tickets = $pdo->query("SELECT t.*, u.username FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5")->fetchAll();

// Fetch recent feedback
$feedback = $pdo->query("SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 5")->fetchAll();

// Fetch recent user activities
$activities = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Dashboard</h1>
            </header>

            <div class="content">
                <!-- Statistics Section -->
                <div class="widget-grid">
                    <?php foreach ($stats as $label => $value): ?>
                        <div class="dashboard-widget">
                            <h3><?php echo $label; ?></h3>
                            <p><?php echo $value; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Recent Activities Section -->
                <div class="dashboard-section">
                    <h2>Recent User Activities</h2>
                    <?php if (empty($activities)): ?>
                        <p>No recent activities.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Recent Tickets Section -->
                <div class="dashboard-section">
                    <h2>Recent Tickets</h2>
                    <?php if (empty($tickets)): ?>
                        <p>No recent tickets.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Subject</th>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo $ticket['id']; ?></td>
                                        <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['username'] ?? 'Guest'); ?></td>
                                        <td><?php echo ucfirst($ticket['status']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Recent Feedback Section -->
                <div class="dashboard-section">
                    <h2>Recent Feedback</h2>
                    <?php if (empty($feedback)): ?>
                        <p>No recent feedback.</p>
                    <?php else: ?>
                        <ul class="feedback-list">
                            <?php foreach ($feedback as $item): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($item['username'] ?? 'Anonymous'); ?>:</strong>
                                    <?php echo htmlspecialchars($item['message']); ?>
                                    <span class="feedback-date"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>