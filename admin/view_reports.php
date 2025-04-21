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
    <style>
        .reports-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            margin: 2rem 0;
        }
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .reports-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }
        .reports-table th, .reports-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .reports-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .reports-table tr:hover {
            background: #f4f8fb;
        }
        @media (max-width: 900px) {
            .reports-container {
                padding: 1rem 0.5rem;
            }
            .reports-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .reports-table th, .reports-table td {
                padding: 8px 6px;
            }
        }
    </style>
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
                <div class="reports-container">
                    <div class="reports-header">
                        <h2>Audit Log (Last 50)</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="reports-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentAudit)): ?>
                                    <?php foreach ($recentAudit as $report): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($report['id']) ?></td>
                                            <td><?= htmlspecialchars($report['user_id']) ?></td>
                                            <td><?= htmlspecialchars($report['action']) ?></td>
                                            <td><?= htmlspecialchars($report['details']) ?></td>
                                            <td><?= htmlspecialchars($report['ip_address']) ?></td>
                                            <td><?= htmlspecialchars($report['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No reports found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
