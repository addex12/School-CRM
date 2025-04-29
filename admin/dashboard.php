<?php
/**
 * 
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
ob_start(); // Start output buffering
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
$pageTitle = "Admin Dashboard";

if (!isset($pdo) || !$pdo) {
    error_log("Database connection not established.");
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
} else {
    error_log("Database connection established successfully.");
}

// School CRM Dashboard widgets (revamped)
$dashboardConfigPath = realpath(__DIR__ . '/../config/dashboard.json');
if ($dashboardConfigPath && is_readable($dashboardConfigPath)) {
    $dashboardConfig = json_decode(file_get_contents($dashboardConfigPath), true);
    $widgets = $dashboardConfig['widgets'] ?? [];
} else {
    $widgets = [];
}

// Fetch new amazing cards data
try {
    // Total Students
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $totalStudents = $stmt->fetchColumn() ?: 0;
    // Total Teachers
    $stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
    $totalTeachers = $stmt->fetchColumn() ?: 0;
    // Total Classes
    $stmt = $pdo->query("SELECT COUNT(*) FROM classes");
    $totalClasses = $stmt->fetchColumn() ?: 0;
    // Total Parents
    $stmt = $pdo->query("SELECT COUNT(*) FROM parents");
    $totalParents = $stmt->fetchColumn() ?: 0;

    // Ongoing Tickets (status = 'open' or 'in_progress')
    $stmt = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open', 'in_progress')");
    $ongoingTickets = $stmt->fetchColumn() ?: 0;

    // Closed Tickets (status = 'closed')
    $stmt = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'closed'");
    $closedTickets = $stmt->fetchColumn() ?: 0;

    // Completed Surveys (where ends_at < NOW())
    $stmt = $pdo->query("SELECT COUNT(*) FROM surveys WHERE ends_at < NOW()");
    $completedSurveys = $stmt->fetchColumn() ?: 0;

    // Active Surveys (where is_active = 1 and starts_at <= NOW() and ends_at >= NOW())
    $stmt = $pdo->query("SELECT COUNT(*) FROM surveys WHERE is_active = 1 AND starts_at <= NOW() AND ends_at >= NOW()");
    $activeSurveys = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    $totalStudents = $totalTeachers = $totalClasses = $totalParents = 0;
    $ongoingTickets = $closedTickets = $completedSurveys = $activeSurveys = 0;
}

// Remove duplicated "Resolved Tickets" card and add new cards
$widgets = array_filter($widgets, function($w) {
    return $w['title'] !== "Resolved Tickets" && $w['title'] !== "Completed Tickets";
});
array_unshift($widgets,
    [
        "title" => "Total Students",
        "icon" => "fa-user-graduate",
        "color" => "blue",
        "count" => $totalStudents
    ],
    [
        "title" => "Total Teachers",
        "icon" => "fa-chalkboard-teacher",
        "color" => "green",
        "count" => $totalTeachers
    ],
    [
        "title" => "Total Classes",
        "icon" => "fa-school",
        "color" => "purple",
        "count" => $totalClasses
    ],
    [
        "title" => "Total Parents",
        "icon" => "fa-users",
        "color" => "teal",
        "count" => $totalParents
    ],
    [
        "title" => "Ongoing Tickets",
        "icon" => "fa-spinner",
        "color" => "orange",
        "count" => $ongoingTickets
    ],
    [
        "title" => "Closed Tickets",
        "icon" => "fa-check-circle",
        "color" => "red",
        "count" => $closedTickets
    ],
    [
        "title" => "Completed Surveys",
        "icon" => "fa-list-check",
        "color" => "yellow",
        "count" => $completedSurveys
    ],
    [
        "title" => "Active Surveys",
        "icon" => "fa-bullhorn",
        "color" => "blue",
        "count" => $activeSurveys
    ]
);

// Fetch unread messages from users to admin
$unreadMessagesCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND receiver_id = ? AND sender_id IN (SELECT id FROM users WHERE role_id != 0)");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadMessagesCount = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    $unreadMessagesCount = 0;
    error_log("Unread Messages Error: " . $e->getMessage());
}

// Fetch recent activity log
$activityLog = [];
try {
    $stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10");
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

// Error log viewer: read last 20 lines of error.log
$errorLogLines = [];
$errorLogPath = realpath(__DIR__ . '/../error_log');
if ($errorLogPath && is_readable($errorLogPath)) {
    $lines = file($errorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $errorLogLines = array_slice($lines, -20);
}

// Parse activity logs for the table
$activityLogs = [];
$logFilePath = realpath(__DIR__ . '/../logs/user_activity.log'); // Assuming logs are stored in this file
if ($logFilePath && is_readable($logFilePath)) {
    $lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        preg_match('/\[(.*?)\] (.*?): (.*)/', $line, $matches);
        if (count($matches) === 4) {
            $activityLogs[] = [
                'timestamp' => $matches[1],
                'action' => $matches[2],
                'details' => $matches[3]
            ];
        }
    }
}

// Limit the number of logs displayed
$activityLogs = array_slice($activityLogs, -20);

// Fetch survey participation stats for chart
$surveyStats = [];
try {
    $stmt = $pdo->query("
        SELECT s.title AS survey_title, COUNT(sr.id) AS response_count
        FROM surveys s
        LEFT JOIN survey_responses sr ON s.id = sr.survey_id
        WHERE s.is_active = 1
          AND s.starts_at <= NOW()
          AND s.ends_at >= NOW()
        GROUP BY s.id
        ORDER BY s.title
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $surveyStats[$row['survey_title']] = $row['response_count'];
    }
} catch (Exception $e) {
    $surveyStats = [];
    error_log("Survey Stats Error: " . $e->getMessage());
}

// Fetch feedback rating distribution for chart
$feedbackRatings = [];
try {
    $stmt = $pdo->query("SELECT rating, COUNT(*) as count FROM feedback GROUP BY rating ORDER BY rating");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $feedbackRatings[$row['rating']] = $row['count'];
    }
} catch (Exception $e) {
    $feedbackRatings = [];
}

// Fetch support ticket status distribution for chart
$ticketStatus = [];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ticketStatus[$row['status']] = $row['count'];
    }
} catch (Exception $e) {
    $ticketStatus = [];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/css/brands.min.css">
    <link rel="stylesheet" href="../assets/css/solid.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
    <style>
        /* 
         * Adugna custom styles for patenting (adugna- prefix)
         * Inspired by ERPNext, compact, flexible, and interactive
         */
        .adugna-btn, .adugna-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            font-size: 0.88rem;
            padding: 0.35rem 0.8rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
            min-height: 32px;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #2563eb 60%, #1741a6 100%);
            color: #fff;
        }
        .adugna-btn:hover {
            background: linear-gradient(90deg, #1741a6 60%, #2563eb 100%);
        }
        .adugna-btn-secondary {
            background: #f1f5f9;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }
        .adugna-btn-secondary:hover {
            background: #e0e7ef;
            color: #1741a6;
        }
        .adugna-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(44,62,80,0.09);
            padding: 1.1rem 0.8rem;
            text-align: center;
            transition: transform 0.13s, box-shadow 0.13s;
            position: relative;
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 120px;
            border-top: 3px solid #e5e7eb;
        }
        .adugna-card:hover {
            transform: translateY(-2px) scale(1.025);
            box-shadow: 0 6px 24px rgba(44,62,80,0.13);
        }
        .adugna-card i {
            font-size: 1.25rem;
            margin-bottom: 0.3rem;
            color: #2563eb;
            background: #f1f5f9;
            border-radius: 50%;
            padding: 0.35em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            min-width: 32px;
            min-height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .adugna-card .adugna-card-title {
            font-size: 1.02rem;
            color: #34495e;
            margin: 0.15rem 0 0.08rem 0;
            font-weight: 600;
        }
        .adugna-card .adugna-card-count {
            font-size: 1.5rem;
            color: #2563eb;
            font-weight: 700;
            margin-bottom: 0.1rem;
        }
        .adugna-card-blue { border-top: 3px solid #2563eb; }
        .adugna-card-green { border-top: 3px solid #22c55e; }
        .adugna-card-orange { border-top: 3px solid #f59e42; }
        .adugna-card-red { border-top: 3px solid #e74c3c; }
        .adugna-card-purple { border-top: 3px solid #8e44ad; }
        .adugna-card-teal { border-top: 3px solid #14b8a6; }
        .adugna-card-yellow { border-top: 3px solid #f1c40f; }
        .adugna-card:not(:last-child) { margin-bottom: 0.4rem; }
        .adugna-quick-links {
            display: flex;
            gap: 0.7rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
            justify-content: flex-start;
        }
        .adugna-quick-link {
            background: #fff;
            border-radius: 7px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 0.6rem 0.8rem;
            text-align: center;
            min-width: 90px;
            transition: box-shadow 0.15s, transform 0.13s;
            font-size: 0.92rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-quick-link:hover {
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .adugna-quick-link i {
            font-size: 1rem;
            margin-bottom: 0.12rem;
            color: #2563eb;
        }
        .adugna-quick-link span {
            margin-top: 0.05rem;
            color: #34495e;
            font-weight: 500;
        }
        .adugna-dashboard {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(120deg, #f4f6fa 60%, #e0e7ef 100%);
            width: 100vw;
            box-sizing: border-box;
        }
        .adugna-main {
            flex: 1;
            padding: 2rem 2.5rem;
            min-width: 0;
            display: flex;
            flex-direction: column;
            width: 100%;
            box-sizing: border-box;
        }
        .adugna-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
        }
        .adugna-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #2563eb;
            font-weight: 800;
            letter-spacing: -1px;
        }
        .adugna-profile-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .adugna-widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        .adugna-section {
            margin-bottom: 1.5rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.1rem 0.8rem;
        }
        .adugna-section h2 {
            font-size: 1.05rem;
            color: #2563eb;
            margin-bottom: 0.8rem;
            border-bottom: 1px solid #f0f2f5;
            padding-bottom: 0.3rem;
            font-weight: 700;
        }
        .adugna-table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            padding: 8px 8px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 0.93rem;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2563eb;
        }
        tr:hover {
            background: #f4f8fb;
        }
        .adugna-section pre.error-log {
            background: #222;
            color: #f1c40f;
            padding: 0.8rem;
            border-radius: 7px;
            font-size: 0.92rem;
            max-height: 250px;
            overflow-y: auto;
        }
        /* Outstanding, interactive hover effect for cards */
        .adugna-card {
            cursor: pointer;
            transition: box-shadow 0.18s, transform 0.18s;
        }
        .adugna-card:active {
            transform: scale(0.98);
            box-shadow: 0 1px 4px rgba(44,62,80,0.09);
        }
        /* Responsive design */
        @media (max-width: 1100px) {
            .adugna-widget-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 800px) {
            .adugna-main {
                padding: 1rem 0.5rem;
            }
            .adugna-widget-grid {
                grid-template-columns: 1fr;
                gap: 0.7rem;
            }
            .adugna-section {
                padding: 0.7rem 0.3rem;
            }
        }
        @media (max-width: 600px) {
            .adugna-main {
                padding: 8px 1px 60px;
            }
            .adugna-card, .adugna-section {
                padding: 0.6rem 0.2rem;
            }
            th, td {
                padding: 6px 3px;
            }
            .adugna-section h2 {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="adugna-dashboard">
        <?php
        // Make unreadMessagesCount available to sidebar
        $ADMIN_UNREAD_MESSAGES = $unreadMessagesCount;
        include __DIR__ . '/includes/admin_sidebar.php';
        ?>
        <div class="adugna-main">
            <header class="adugna-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="adugna-profile-menu">
                    <?php if ($unreadMessagesCount > 0): ?>
                        <a href="messages.php" class="adugna-btn-secondary" style="position:relative;">
                            <i class="fas fa-envelope"></i>
                            <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:#fff;border-radius:50%;padding:2px 7px;font-size:0.78em;font-weight:600;">
                                <?= $unreadMessagesCount ?>
                            </span>
                            <span style="margin-left:1.3em;">New</span>
                        </a>
                    <?php endif; ?>
                    <a href="profile.php" class="adugna-btn-secondary">
                        <i class="fas fa-user-circle"></i>
                    </a>
                </div>
            </header>
            <div class="content">

                <!-- Quick Links Section -->
                <div class="adugna-quick-links">
                    <a href="users.php" class="adugna-quick-link"><i class="fas fa-users"></i><span>Manage Users</span></a>
                    <a href="surveys.php" class="adugna-quick-link"><i class="fas fa-poll"></i><span>Surveys</span></a>
                    <a href="feedback.php" class="adugna-quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                    <a href="support_tickets.php" class="adugna-quick-link"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
                </div>

                <!-- Widgets Section -->
                <div class="adugna-widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <?php
                        // Ensure 'count' key exists to avoid undefined index warning
                        if (!isset($widget['count'])) {
                            $widget['count'] = 0;
                        }
                        ?>
                        <div class="adugna-card adugna-card-<?= htmlspecialchars($widget['color']) ?>">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <div class="adugna-card-count"><?= htmlspecialchars($widget['count']) ?></div>
                            <div class="adugna-card-title"><?= htmlspecialchars($widget['title']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Survey Participation Chart -->
                <div class="adugna-section">
                    <h2>Survey Participation</h2>
                    <canvas id="surveyParticipationChart" height="80"></canvas>
                </div>

                <!-- Feedback Ratings Chart -->
                <div class="adugna-section">
                    <h2>Feedback Ratings</h2>
                    <canvas id="feedbackRatingsChart" height="80"></canvas>
                </div>

                <!-- Support Ticket Status Chart -->
                <div class="adugna-section">
                    <h2>Support Ticket Status</h2>
                    <canvas id="ticketStatusChart" height="80"></canvas>
                </div>

                <!-- System Stats Section -->
                <div class="adugna-section">
                    <h2>System Stats</h2>
                    <ul>
                        <li>PHP Version: <?= phpversion() ?></li>
                        <li>Server Software: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></li>
                        <li>Database Host: <?= htmlspecialchars(DB_HOST ?? 'localhost') ?></li>
                        <li>Current Time: <?= date('Y-m-d H:i:s') ?></li>
                    </ul>
                </div>

                <!-- Error Log Section -->
                <div class="adugna-section">
                    <h2>Recent Error Log</h2>
                    <?php if (!empty($errorLogLines)): ?>
                        <pre class="error-log"><?= htmlspecialchars(implode("\n", $errorLogLines)) ?></pre>
                    <?php else: ?>
                        <p>No recent errors found or error.log not readable.</p>
                    <?php endif; ?>
                </div>

                <!-- Activity Log Section -->
                <div class="adugna-section">
                    <h2>Recent Activity Log</h2>
                    <div class="adugna-table-container">
                        <table>
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

                <!-- User Activity Logs Section -->
                <div class="adugna-section">
                    <h2>User Activity Logs</h2>
                    <div class="adugna-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activityLogs)): ?>
                                    <?php foreach ($activityLogs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                            <td><?= htmlspecialchars($log['action']) ?></td>
                                            <td><?= htmlspecialchars($log['details']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No activity logs found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feedback Section -->
                <div class="adugna-section">
                    <h2>Recent Feedback</h2>
                    <div class="adugna-table-container">
                        <table>
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
                <div class="adugna-section">
                    <h2>Recent Support Tickets</h2>
                    <div class="adugna-table-container">
                        <table>
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
    <?php include 'includes/footer.php'; ?>

    <script>
        // Survey Participation Chart
        (function() {
            const ctx = document.getElementById('surveyParticipationChart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_keys($surveyStats)) ?>,
                        datasets: [{
                            label: 'Responses',
                            data: <?= json_encode(array_values($surveyStats)) ?>,
                            backgroundColor: '#3b82f6'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true },
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        })();

        // Feedback Ratings Chart
        (function() {
            const ctx = document.getElementById('feedbackRatingsChart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: <?= json_encode(array_keys($feedbackRatings)) ?>,
                        datasets: [{
                            label: 'Feedback Ratings',
                            data: <?= json_encode(array_values($feedbackRatings)) ?>,
                            backgroundColor: ['#3b82f6', '#f59e42', '#f1c40f', '#27ae60', '#e74c3c']
                        }]
                    },
                    options: { responsive: true }
                });
            }
        })();

        // Support Ticket Status Chart
        (function() {
            const ctx = document.getElementById('ticketStatusChart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_keys($ticketStatus)) ?>,
                        datasets: [{
                            label: 'Tickets',
                            data: <?= json_encode(array_values($ticketStatus)) ?>,
                            backgroundColor: ['#3b82f6', '#e74c3c', '#f1c40f', '#27ae60']
                        }]
                    },
                    options: { responsive: true }
                });
            }
        })();
    </script>
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>
