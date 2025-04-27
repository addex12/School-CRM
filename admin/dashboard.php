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
    <title>ERPNext-Style Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .admin-dashboard {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 240px;
            background-color: #2c3e50;
            color: #fff;
            padding: 1rem;
            position: fixed;
            top: 0;
            bottom: 0;
            overflow-y: auto;
        }

        .admin-sidebar h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .admin-sidebar a {
            display: block;
            color: #fff;
            text-decoration: none;
            margin: 0.5rem 0;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .admin-sidebar a:hover {
            background-color: #34495e;
        }

        .admin-main {
            flex: 1;
            margin-left: 240px;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                position: absolute;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .admin-sidebar.active {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .toggle-sidebar {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                background: #2c3e50;
                color: #fff;
                border: none;
                padding: 0.5rem;
                border-radius: 4px;
                cursor: pointer;
                z-index: 3;
            }
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 1.5rem;
            color: #2c3e50;
        }

        .btn-primary {
            background-color: #2e8bff;
            color: #fff;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: box-shadow 0.3s;
        }

        .btn-primary:hover {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .dashboard-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .dashboard-card h3 {
            font-size: 1rem;
            margin: 0.5rem 0;
            color: #2c3e50;
        }

        .dashboard-card p {
            font-size: 0.875rem;
            color: #757575;
        }

        .dashboard-card i {
            font-size: 1.5rem;
            color: #2e8bff;
            margin-bottom: 0.5rem;
        }

        .chart-container {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }

        .chart-container h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
    </style>
</head>

<body>
    <button class="toggle-sidebar">☰</button>
    <div class="admin-dashboard">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <a href="#">Dashboard</a>
            <a href="#">Users</a>
            <a href="#">Reports</a>
            <a href="#">Settings</a>
        </div>
        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <button class="btn-primary">Add New</button>
            </div>
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <i class="fas fa-users"></i>
                    <h3>150</h3>
                    <p>Total Users</p>
                </div>
                <div class="dashboard-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>$12,000</h3>
                    <p>Monthly Revenue</p>
                </div>
                <div class="dashboard-card">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>25</h3>
                    <p>Open Tickets</p>
                </div>
            </div>
            <div class="chart-container">
                <h3>Monthly Sales</h3>
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Expense Distribution</h3>
                <canvas id="pieChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Quarterly Revenue</h3>
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('.toggle-sidebar').addEventListener('click', function () {
            document.querySelector('.admin-sidebar').classList.toggle('active');
        });

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales ($)',
                    data: [1200, 1900, 3000, 5000, 2000, 3000],
                    backgroundColor: '#2e8bff',
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Marketing', 'Operations', 'Development'],
                datasets: [{
                    data: [40, 30, 30],
                    backgroundColor: ['#2e8bff', '#e0e0e0', '#ff5858']
                }]
            },
            options: {
                responsive: true
            }
        });

        // Line Chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [5000, 10000, 15000, 20000],
                    borderColor: '#2e8bff',
                    backgroundColor: 'rgba(46, 139, 255, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>
<?php
// Flush output buffer
ob_end_flush();
?>