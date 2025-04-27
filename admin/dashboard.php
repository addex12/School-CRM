<?php
/**
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
    $widgets = $dashboardConfig['widgets'] ?? [
        [
            "title" => "Total Users",
            "icon" => "fa-users",
            "color" => "blue",
            "query" => "SELECT COUNT(*) FROM users"
        ],
        [
            "title" => "Active Users",
            "icon" => "fa-user-check",
            "color" => "green",
            "query" => "SELECT COUNT(*) FROM users WHERE status = 'active'"
        ],
        [
            "title" => "Inactive Users",
            "icon" => "fa-user-times",
            "color" => "red",
            "query" => "SELECT COUNT(*) FROM users WHERE status = 'inactive'"
        ],
        [
            "title" => "Open Tickets",
            "icon" => "fa-ticket-alt",
            "color" => "red",
            "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'"
        ],
        [
            "title" => "In Progress Tickets",
            "icon" => "fa-spinner",
            "color" => "blue",
            "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'in_progress'"
        ],
        [
            "title" => "On Hold Tickets",
            "icon" => "fa-pause-circle",
            "color" => "yellow",
            "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'on_hold'"
        ],
        [
            "title" => "Resolved Tickets",
            "icon" => "fa-check-circle",
            "color" => "green",
            "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'resolved'"
        ]
    ];
} else {
    error_log("Dashboard configuration file not found or unreadable.");
    $widgets = [];
}

foreach ($widgets as &$widget) {
    try {
        $stmt = $pdo->query($widget['query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        $widget['count'] = "Error";
        error_log("Widget Error: " . $e->getMessage());
    }
}

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
    $stmt = $pdo->query("SELECT s.title, COUNT(sr.id) as responses
        FROM surveys s
        LEFT JOIN survey_responses sr ON s.id = sr.survey_id
        GROUP BY s.id
        ORDER BY responses DESC
        LIMIT 7");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $surveyStats[$row['title']] = $row['responses'];
    }
} catch (Exception $e) {
    $surveyStats = [];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
        }

        .admin-dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            padding: 1rem;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }

        .admin-sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }

        .admin-sidebar a:hover {
            background-color: #495057;
            color: #fff;
        }

        /* Main Content */
        .admin-main {
            margin-left: 250px;
            padding: 1rem;
            flex: 1;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 200px;
            }

            .admin-main {
                margin-left: 200px;
            }
        }

        @media (max-width: 576px) {
            .admin-sidebar {
                position: absolute;
                width: 100%;
                height: auto;
            }

            .admin-main {
                margin-left: 0;
                padding-top: 200px;
            }
        }

        /* Header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .admin-header h1 {
            font-size: 1.5rem;
            color: #343a40;
        }

        .admin-header .btn {
            background-color: #5e64ff;
            color: #fff;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .admin-header .btn:hover {
            background-color: #4a52d4;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Quick Links */
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .quick-link {
            flex: 1 1 calc(25% - 1rem);
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-align: center;
            padding: 0.75rem;
            text-decoration: none;
            color: #343a40;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .quick-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .quick-link i {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #5e64ff;
        }

        @media (max-width: 768px) {
            .quick-link {
                flex: 1 1 calc(50% - 1rem);
            }
        }

        @media (max-width: 576px) {
            .quick-link {
                flex: 1 1 100%;
            }
        }

        /* Widgets */
        .widget-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .dashboard-widget {
            flex: 1 1 calc(33.333% - 1rem);
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-align: center;
            padding: 0.75rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .dashboard-widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .dashboard-widget i {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .widget-blue {
            color: #5e64ff;
        }

        .widget-green {
            color: #28a745;
        }

        .widget-red {
            color: #ff5858;
        }

        .widget-yellow {
            color: #ffc107;
        }

        @media (max-width: 768px) {
            .dashboard-widget {
                flex: 1 1 calc(50% - 1rem);
            }
        }

        @media (max-width: 576px) {
            .dashboard-widget {
                flex: 1 1 100%;
            }
        }

        /* Charts */
        .dashboard-section {
            margin-bottom: 2rem;
        }

        .dashboard-section h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #343a40;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        table th, table td {
            padding: 0.75rem;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        table th {
            background-color: #f8f9fa;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-sidebar">
            <!-- Sidebar content -->
            <h2>Admin Panel</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="surveys.php">Surveys</a>
            <a href="feedback.php">Feedback</a>
            <a href="support_tickets.php">Support Tickets</a>
            <a href="events.php">Events</a>
        </div>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($unreadMessagesCount > 0): ?>
                    <button class="btn">
                        <i class="fas fa-envelope"></i> <?= $unreadMessagesCount ?> New Messages
                    </button>
                <?php endif; ?>
            </header>
            <div class="content">
                <!-- Quick Links -->
                <div class="quick-links">
                    <a href="users.php" class="quick-link"><i class="fas fa-users"></i><span>Manage Users</span></a>
                    <a href="surveys.php" class="quick-link"><i class="fas fa-poll"></i><span>Surveys</span></a>
                    <a href="feedback.php" class="quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                    <a href="support_tickets.php" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
                </div>

                <!-- Widgets -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3><?= htmlspecialchars($widget['count']) ?></h3>
                            <p><?= htmlspecialchars($widget['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Charts -->
                <div class="dashboard-section">
                    <h2>Survey Participation</h2>
                    <canvas id="surveyParticipationChart"></canvas>
                </div>
                <div class="dashboard-section">
                    <h2>Feedback Ratings</h2>
                    <canvas id="feedbackRatingsChart"></canvas>
                </div>
                <div class="dashboard-section">
                    <h2>Support Ticket Status</h2>
                    <canvas id="ticketStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
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
                            backgroundColor: '#5e64ff'
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
                            backgroundColor: ['#5e64ff', '#f59e42', '#f1c40f', '#28a745', '#ff5858']
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
                            backgroundColor: ['#5e64ff', '#ff5858', '#f1c40f', '#28a745']
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
