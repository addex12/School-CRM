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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting. Admin_sidebar/footer CSS untouched. */
        body { background: #f5f7fa; }
        .adugna-main {
            margin-left: 250px;
            padding: 2vw 2vw 2vw 2vw;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-header {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto 1.5rem auto;
            text-align: center;
        }
        .adugna-header h1 {
            color: #215967;
            font-weight: 700;
            font-size: clamp(1.3rem,2.5vw,2rem);
            margin: 0 0 0.5em 0;
        }
        .adugna-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 1.5rem;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto 2rem auto;
        }
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px 0 rgba(80, 112, 255, 0.10), 0 2px 8px 0 rgba(80, 112, 255, 0.04);
            border: 1px solid #e5e7eb;
            padding: 1.3rem 1.2rem 1.2rem 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
            min-height: 120px;
            transition: box-shadow 0.2s, border 0.2s;
        }
        .adugna-card-title {
            font-size: 1.08rem;
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.7em;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .adugna-card-title i {
            font-size: 1em;
        }
        .adugna-card-value {
            font-size: 1.5em;
            font-weight: 700;
            color: #4338ca;
            margin-bottom: 0.2em;
        }
        .adugna-card-desc {
            color: #888;
            font-size: 0.97em;
        }
        .adugna-table-responsive { width: 100%; overflow-x: auto; }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 0.98em;
        }
        .adugna-table th, .adugna-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .adugna-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
            font-size: 1em;
        }
        .adugna-table tr:hover { background: #f4f8fb; }
        .adugna-table td:last-child, .adugna-table th:last-child { text-align: right; }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.4em;
            padding: 0.13rem 0.7rem;
            font-size: 0.92em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i { font-size: 0.92em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        @media (max-width: 900px) {
            .adugna-main { padding: 1.2rem 1vw; }
            .adugna-cards-grid { grid-template-columns: 1fr; gap: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-table th, .adugna-table td { padding: 7px 4px; font-size: 0.93em; }
            .adugna-main { padding: 7px 2px 80px; }
            .adugna-card { padding: 0.7rem 2vw; }
        }
        @media (max-width: 400px) {
            .adugna-card { padding: 2px; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <!-- Adugna Gizaw: Responsive summary cards grid -->
            <div class="adugna-cards-grid">
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-list-alt"></i>Audit Log Actions</div>
                    <div class="adugna-card-value"><?= count($auditSummary) ?></div>
                    <div class="adugna-card-desc">Top 10 actions in audit log</div>
                </div>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-poll"></i>Surveys</div>
                    <div class="adugna-card-value"><?= (int)$surveyCount ?></div>
                    <div class="adugna-card-desc">Total surveys</div>
                </div>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-check-circle"></i>Survey Responses</div>
                    <div class="adugna-card-value"><?= (int)$surveyResponseCount ?></div>
                    <div class="adugna-card-desc">Total responses</div>
                </div>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-comments"></i>Feedback</div>
                    <div class="adugna-card-value"><?= (int)$feedbackCount ?></div>
                    <div class="adugna-card-desc">Avg. Rating: <?= $avgRating ? number_format($avgRating, 2) : 'N/A' ?></div>
                </div>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-ticket-alt"></i>Support Tickets</div>
                    <div class="adugna-card-value"><?= (int)$ticketCount ?></div>
                    <div class="adugna-card-desc">Open: <?= (int)$openTickets ?> | Closed: <?= (int)$closedTickets ?></div>
                </div>
                <?php if (!empty($attendanceSummary)): ?>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-user-check"></i>Attendance</div>
                    <div class="adugna-card-value">
                        <?php foreach ($attendanceSummary as $a): ?>
                            <span style="font-size:0.98em;display:inline-block;margin-right:0.7em;">
                                <?= htmlspecialchars($a['status']) ?>: <b><?= (int)$a['count'] ?></b>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="adugna-card-desc">Attendance summary</div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Adugna Gizaw: Recent activity tables -->
            <div class="adugna-cards-grid" style="grid-template-columns:1fr 1fr;max-width:1100px;">
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-history"></i>Recent Audit Log</div>
                    <div class="adugna-table-responsive">
                        <table class="adugna-table">
                            <thead>
                                <tr><th>User</th><th>Action</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAudit as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['username'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-poll"></i>Recent Surveys</div>
                    <div class="adugna-table-responsive">
                        <table class="adugna-table">
                            <thead>
                                <tr><th>Title</th><th>Created</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSurveys as $survey): ?>
                                <tr>
                                    <td><?= htmlspecialchars($survey['title'] ?? 'N/A') ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($survey['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-comments"></i>Recent Feedback</div>
                    <div class="adugna-table-responsive">
                        <table class="adugna-table">
                            <thead>
                                <tr><th>User</th><th>Rating</th><th>Comment</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentFeedback as $fb): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fb['username'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($fb['rating'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($fb['comment'] ?? '-') ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($fb['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="adugna-card">
                    <div class="adugna-card-title"><i class="fas fa-ticket-alt"></i>Recent Tickets</div>
                    <div class="adugna-table-responsive">
                        <table class="adugna-table">
                            <thead>
                                <tr><th>User</th><th>Subject</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ticket['username'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($ticket['subject'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($ticket['status'] ?? '-')) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
