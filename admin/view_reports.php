<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "View Reports";

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <?= htmlspecialchars($pageTitle) ?>
            </header>
            <div class="admin-content">
                <p>Report details will be displayed here.</p>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
