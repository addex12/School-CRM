<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Comprehensive Reports";

// Fetch audit log summary
$auditSummary = $pdo->query("SELECT action, COUNT(*) as count FROM audit_logs GROUP BY action ORDER BY count DESC LIMIT 10")->fetchAll();
$recentAudit = $pdo->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10")->fetchAll();

// Fetch survey summary
$surveyCount = $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
$surveyResponseCount = $pdo->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();
$recentSurveys = $pdo->query("SELECT * FROM surveys ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Feedback summary
$feedbackCount = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avgRating = $pdo->query("SELECT AVG(rating) FROM feedback WHERE rating IS NOT NULL")->fetchColumn();
$recentFeedback = $pdo->query("SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 5")->fetchAll();

// Support ticket summary
$ticketCount = $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
$closedTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'closed'")->fetchColumn();
$recentTickets = $pdo->query("SELECT t.*, u.username FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5")->fetchAll();

// Attendance summary (if exists)
$attendanceSummary = [];
try {
    $attendanceSummary = $pdo->query("SELECT status, COUNT(*) as count FROM attendance GROUP BY status")->fetchAll();
} catch (Exception $e) {
    // Attendance table may not exist
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <section class="dashboard-section">
                    <h2>Audit Log Summary</h2>
                    <table><thead><tr><th>Action</th><th>Count</th></tr></thead><tbody>
                        <?php foreach ($auditSummary as $row): ?>
                        <tr><td><?= htmlspecialchars($row['action']) ?></td><td><?= htmlspecialchars($row['count']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table>
                    <h3>Recent Activity</h3>
                    <table><thead><tr><th>User</th><th>Action</th><th>Details</th><th>Time</th></tr></thead><tbody>
                        <?php foreach ($recentAudit as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['details'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody></table>
                </section>
                <section class="dashboard-section">
                    <h2>Survey Reports</h2>
                    <p>Total Surveys: <?= htmlspecialchars($surveyCount) ?> | Total Responses: <?= htmlspecialchars($surveyResponseCount) ?></p>
                    <h3>Recent Surveys</h3>
                    <table><thead><tr><th>Title</th><th>Created At</th></tr></thead><tbody>
                        <?php foreach ($recentSurveys as $survey): ?>
                        <tr><td><?= htmlspecialchars($survey['title']) ?></td><td><?= htmlspecialchars($survey['created_at']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table>
                </section>
                <section class="dashboard-section">
                    <h2>Feedback Summary</h2>
                    <p>Total Feedback: <?= htmlspecialchars($feedbackCount) ?> | Average Rating: <?= number_format($avgRating,1) ?></p>
                    <h3>Recent Feedback</h3>
                    <table><thead><tr><th>User</th><th>Subject</th><th>Message</th><th>Rating</th><th>Time</th></tr></thead><tbody>
                        <?php foreach ($recentFeedback as $fb): ?>
                        <tr>
                            <td><?= htmlspecialchars($fb['username'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($fb['subject']) ?></td>
                            <td><?= htmlspecialchars($fb['message']) ?></td>
                            <td><?= htmlspecialchars($fb['rating']) ?></td>
                            <td><?= htmlspecialchars($fb['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody></table>
                </section>
                <section class="dashboard-section">
                    <h2>Support Ticket Summary</h2>
                    <p>Total Tickets: <?= htmlspecialchars($ticketCount) ?> | Open: <?= htmlspecialchars($openTickets) ?> | Closed: <?= htmlspecialchars($closedTickets) ?></p>
                    <h3>Recent Tickets</h3>
                    <table><thead><tr><th>User</th><th>Subject</th><th>Status</th><th>Created At</th></tr></thead><tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                        <tr>
                            <td><?= htmlspecialchars($ticket['username'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                            <td><?= htmlspecialchars($ticket['status']) ?></td>
                            <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody></table>
                </section>
                <?php if (!empty($attendanceSummary)): ?>
                <section class="dashboard-section">
                    <h2>Attendance Summary</h2>
                    <table><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
                        <?php foreach ($attendanceSummary as $row): ?>
                        <tr><td><?= htmlspecialchars($row['status']) ?></td><td><?= htmlspecialchars($row['count']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
