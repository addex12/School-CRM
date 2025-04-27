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
            "title" => "Total Courses",
            "icon" => "fa-book",
            "color" => "purple",
            "query" => "SELECT COUNT(*) FROM courses"
        ],
        [
            "title" => "Enrolled Students",
            "icon" => "fa-user-graduate",
            "color" => "orange",
            "query" => "SELECT COUNT(*) FROM course_enrollments"
        ],
        [
            "title" => "New Feedback",
            "icon" => "fa-comments",
            "color" => "teal",
            "query" => "SELECT COUNT(*) FROM feedback WHERE is_read = 0"
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
        ],
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

// Fetch recent announcements
$announcements = [];
try {
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Announcements Error: " . $e->getMessage());
}

// Fetch system health status
$systemHealth = [
    'php_version' => phpversion() ?? 'Unknown',
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_status' => isset($pdo) && $pdo ? 'Connected' : 'Disconnected',
    'current_time' => date('Y-m-d H:i:s') ?? 'Unknown',
];

// Fetch user role distribution for chart
$userRoleDistribution = [];
try {
    $stmt = $pdo->query("SELECT roles.name, COUNT(users.id) as count 
                         FROM roles 
                         LEFT JOIN users ON roles.id = users.role_id 
                         GROUP BY roles.id");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $userRoleDistribution[$row['name']] = $row['count'];
    }
} catch (Exception $e) {
    $userRoleDistribution = [];
    error_log("User Role Distribution Error: " . $e->getMessage());
}

// Fetch monthly new users for chart
$monthlyNewUsers = [];
try {
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                         FROM users 
                         GROUP BY month 
                         ORDER BY month ASC");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $monthlyNewUsers[$row['month']] = $row['count'];
    }
} catch (Exception $e) {
    $monthlyNewUsers = [];
    error_log("Monthly New Users Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background: #f4f6fa;
            margin: 0;
            padding: 0;
        }
        .admin-dashboard {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .admin-main {
            flex: 1;
            padding: 1.5rem;
            margin-left: 240px;
            transition: margin-left 0.2s;
        }
        .admin-main.collapsed {
            margin-left: 60px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .dashboard-header h1 {
            font-size: 1.5rem;
            color: #34495e;
            margin: 0;
        }
        .dashboard-header .btn-primary {
            background: #2e8bff;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            font-weight: 500;
            transition: box-shadow 0.2s;
        }
        .dashboard-header .btn-primary:hover {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            text-align: center;
        }
        .card h3 {
            font-size: 1.2rem;
            color: #34495e;
            margin: 0.5rem 0;
        }
        .card p {
            font-size: 0.9rem;
            color: #757575;
        }
        .chart-container {
            margin-top: 2rem;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        .chart-container h3 {
            font-size: 1.2rem;
            color: #34495e;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        @media (max-width: 480px) {
            .dashboard-header h1 {
                font-size: 1.2rem;
            }
            .dashboard-header .btn-primary {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Dashboard</h1>
                <button class="btn-primary"><i class="fas fa-plus"></i> Add New</button>
            </div>
            <div class="cards-container">
                <div class="card">
                    <i class="fas fa-users fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Users</h3>
                    <p><?= $totalUsers ?></p>
                </div>
                <div class="card">
                    <i class="fas fa-chalkboard-teacher fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Teachers</h3>
                    <p><?= $totalTeachers ?></p>
                </div>
                <div class="card">
                    <i class="fas fa-user-graduate fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Students</h3>
                    <p><?= $totalStudents ?></p>
                </div>
                <div class="card">
                    <i class="fas fa-poll fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Surveys</h3>
                    <p><?= $totalSurveys ?></p>
                </div>
            </div>
            <div class="chart-container">
                <h3>Monthly Sales Data</h3>
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Expense Distribution</h3>
                <canvas id="pieChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Quarterly Revenue Trends</h3>
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>
    <script>
        const barChartData = {
            labels: <?= json_encode(array_column($monthlySalesData, 'month')) ?>,
            datasets: [{
                label: 'Sales ($)',
                data: <?= json_encode(array_column($monthlySalesData, 'total')) ?>,
                backgroundColor: '#2e8bff'
            }]
        };

        const pieChartData = {
            labels: <?= json_encode(array_column($expenseDistribution, 'category')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($expenseDistribution, 'total')) ?>,
                backgroundColor: ['#2e8bff', '#ff6384', '#ffcd56', '#4bc0c0']
            }]
        };

        const lineChartData = {
            labels: <?= json_encode(array_column($quarterlyRevenue, 'quarter')) ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?= json_encode(array_column($quarterlyRevenue, 'total')) ?>,
                borderColor: '#2e8bff',
                fill: false
            }]
        };

        new Chart(document.getElementById('barChart'), { type: 'bar', data: barChartData });
        new Chart(document.getElementById('pieChart'), { type: 'pie', data: pieChartData });
        new Chart(document.getElementById('lineChart'), { type: 'line', data: lineChartData });
    </script>
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>