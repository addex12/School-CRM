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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-main {
            margin-left: 260px;
            padding: 2rem;
            transition: margin-left 0.2s;
        }

        @media (max-width: 900px) {
            .admin-main {
                margin-left: 60px;
            }
        }

        @media (max-width: 600px) {
            .admin-main {
                margin-left: 0;
            }
        }

        .content {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.07);
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>View Reports</h1>
            </header>
            <div class="content">
                <p>Report details will be displayed here.</p>
            </div>
        </div>
    </div>
</body>
</html>
