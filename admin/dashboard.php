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
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            /* Compact grid layout */
            gap: 0;
            /* Remove gap between links */
        }

        .quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            font-size: 0.75rem;
            /* Smaller font size */
            background: linear-gradient(135deg, #f0f4f7, #dfe6ed);
            border-radius: 6px;
            /* Smaller border radius */
            color: #34495e;
            text-decoration: none;
            transition: background 0.3s, box-shadow 0.3s, transform 0.3s;
            height: 80px;
            /* Reduced height */
            text-align: center;
            border: 1px solid #e0e6ed;
            /* Add border to separate links visually */
        }

        .quick-link:hover {
            background: linear-gradient(135deg, #e0e6ed, #cfd8e3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        .quick-link i {
            font-size: 1.2rem;
            /* Smaller icon size */
            margin-bottom: 0.25rem;
        }

        @media (max-width: 900px) {
            .admin-main {
                margin-left: 60px;
            }

            .dashboard-widgets-and-links {
                grid-template-columns: 1fr;
                /* Stack widgets and links vertically */
            }
        }

        @media (max-width: 600px) {
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }

            .dashboard-widget {
                flex: 1 1 100%;
                /* Stack widgets vertically */
            }
        }

        .admin-header {
            text-align: center; /* Center align the title */
            margin-bottom: 1rem;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Add GSAP animations for widgets
            gsap.from(".dashboard-widget", {
                opacity: 0,
                y: 50,
                duration: 0.8,
                stagger: 0.2
            });

            // Add GSAP animations for sections
            gsap.from(".dashboard-section", {
                opacity: 0,
                y: 50,
                duration: 0.8,
                stagger: 0.3
            });

            // Survey Participation Chart
            const surveyCtx = document.getElementById('surveyChart').getContext('2d');
            new Chart(surveyCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($surveyStats)) ?>,
                    datasets: [{
                        label: 'Survey Responses',
                        data: <?= json_encode(array_values($surveyStats)) ?>,
                        backgroundColor: '#5e64ff',
                        borderColor: '#34495e',
                        borderWidth: 1
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

            // Feedback Ratings Chart
            const feedbackCtx = document.getElementById('feedbackChart').getContext('2d');
            new Chart(feedbackCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_keys($feedbackRatings)) ?>,
                    datasets: [{
                        label: 'Feedback Ratings',
                        data: <?= json_encode(array_values($feedbackRatings)) ?>,
                        backgroundColor: ['#5e64ff', '#f0f4f7', '#ff5858', '#34495e', '#f39c12']
                    }]
                },
                options: {
                    responsive: true
                }
            });

            // Support Ticket Status Chart
            const ticketCtx = document.getElementById('ticketChart').getContext('2d');
            new Chart(ticketCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($ticketStatus)) ?>,
                    datasets: [{
                        label: 'Ticket Status',
                        data: <?= json_encode(array_values($ticketStatus)) ?>,
                        backgroundColor: ['#5e64ff', '#f0f4f7', '#ff5858', '#34495e', '#2ecc71']
                    }]
                },
                options: {
                    responsive: true
                }
            });

            // System Health Line Chart (Example)
            const systemHealthCtx = document.getElementById('systemHealthChart').getContext('2d');
            new Chart(systemHealthCtx, {
                type: 'line',
                data: {
                    labels: ['PHP Version', 'Server Software', 'Database Status', 'Current Time'],
                    datasets: [{
                        label: 'System Health Metrics',
                        data: [<?= json_encode($systemHealth['php_version']) ?>, <?= json_encode($systemHealth['server_software']) ?>, <?= json_encode($systemHealth['database_status']) ?>, <?= json_encode($systemHealth['current_time']) ?>],
                        backgroundColor: 'rgba(94, 100, 255, 0.2)',
                        borderColor: '#5e64ff',
                        borderWidth: 2
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

            // User Role Distribution Chart
            const roleCtx = document.getElementById('roleChart').getContext('2d');
            new Chart(roleCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($userRoleDistribution)) ?>,
                    datasets: [{
                        label: 'User Roles',
                        data: <?= json_encode(array_values($userRoleDistribution)) ?>,
                        backgroundColor: '#5e64ff',
                        borderColor: '#34495e',
                        borderWidth: 1
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

            // Monthly New Users Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_keys($monthlyNewUsers)) ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?= json_encode(array_values($monthlyNewUsers)) ?>,
                        backgroundColor: 'rgba(94, 100, 255, 0.2)',
                        borderColor: '#5e64ff',
                        borderWidth: 2
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
        });
    </script>
</head>

<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <!-- Dashboard Widgets and Quick Links Section -->
                <div class="dashboard-widgets-and-links">
                    <div class="widget-grid">
                        <?php foreach ($widgets as $widget): ?>
                            <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                                <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                                <h3><?= htmlspecialchars($widget['count']) ?></h3>
                                <p><?= htmlspecialchars($widget['title']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="quick-links">
                        <a href="users.php" class="quick-link"><i class="fas fa-users"></i><span>Manage Users</span></a>
                        <a href="surveys.php" class="quick-link"><i class="fas fa-poll"></i><span>Surveys</span></a>
                        <a href="feedback.php" class="quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                        <a href="support_tickets.php" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
                        <a href="events.php" class="quick-link"><i class="fas fa-calendar-alt"></i><span>Events</span></a>
                        <a href="knowledge_base.php" class="quick-link"><i class="fas fa-book"></i><span>Knowledgebase</span></a>
                        <a href="announcements.php" class="quick-link"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="dashboard-section">
                    <h2>Survey Participation</h2>
                    <canvas id="surveyChart"></canvas>
                </div>
                <div class="dashboard-section">
                    <h2>Feedback Ratings</h2>
                    <canvas id="feedbackChart"></canvas>
                </div>
                <div class="dashboard-section">
                    <h2>Support Ticket Status</h2>
                    <canvas id="ticketChart"></canvas>
                </div>

                <div class="dashboard-section">
                    <h2>User Role Distribution</h2>
                    <canvas id="roleChart"></canvas>
                </div>
                <div class="dashboard-section">
                    <h2>Monthly New Users</h2>
                    <canvas id="monthlyChart"></canvas>
                </div>


            </div>
        </div>
        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
</body>

</html>
<?php
// Flush output buffer
ob_end_flush();
?>