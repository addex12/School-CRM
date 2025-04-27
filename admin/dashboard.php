<?php
ob_start();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
$pageTitle = "Admin Dashboard";

// Enhanced database analysis
$stats = [];
try {
    // Get table counts
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $stats['tables'][$table] = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    // Get user growth data
    $stats['user_growth'] = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        GROUP BY DATE(created_at) 
        ORDER BY date
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get activity trends
    $stats['activity_trends'] = $pdo->query("
        SELECT activity_type, COUNT(*) as count 
        FROM activity_logs 
        GROUP BY activity_type 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get feedback distribution
    $stats['feedback_distribution'] = $pdo->query("
        SELECT rating, COUNT(*) as count 
        FROM feedback 
        GROUP BY rating 
        ORDER BY rating
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get ticket status distribution
    $stats['ticket_status'] = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM support_tickets 
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get course enrollment data
    $stats['course_enrollments'] = $pdo->query("
        SELECT c.title, COUNT(e.id) as enrollments 
        FROM courses c 
        LEFT JOIN course_enrollments e ON c.id = e.course_id 
        GROUP BY c.id 
        ORDER BY enrollments DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get system performance metrics
    $stats['performance'] = [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'db_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
        'server_load' => sys_getloadavg()[0] ?? 'N/A',
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true)
    ];

} catch (Exception $e) {
    error_log("Database analysis error: " . $e->getMessage());
}

// Original widget configuration with enhancements
$dashboardConfigPath = realpath(__DIR__ . '/../config/dashboard.json');
$widgets = [];
if ($dashboardConfigPath && is_readable($dashboardConfigPath)) {
    $dashboardConfig = json_decode(file_get_contents($dashboardConfigPath), true);
    $widgets = $dashboardConfig['widgets'] ?? [];
}

// Default widgets if config not found
if (empty($widgets)) {
    $widgets = [
        ["title" => "Total Users", "icon" => "fa-users", "color" => "blue", "query" => "SELECT COUNT(*) FROM users"],
        ["title" => "Active Users", "icon" => "fa-user-check", "color" => "green", "query" => "SELECT COUNT(*) FROM users WHERE status = 'active'"],
        ["title" => "Inactive Users", "icon" => "fa-user-times", "color" => "red", "query" => "SELECT COUNT(*) FROM users WHERE status = 'inactive'"],
        ["title" => "Total Courses", "icon" => "fa-book", "color" => "purple", "query" => "SELECT COUNT(*) FROM courses"],
        ["title" => "Enrolled Students", "icon" => "fa-user-graduate", "color" => "orange", "query" => "SELECT COUNT(*) FROM course_enrollments"],
        ["title" => "New Feedback", "icon" => "fa-comments", "color" => "teal", "query" => "SELECT COUNT(*) FROM feedback WHERE is_read = 0"],
        ["title" => "Open Tickets", "icon" => "fa-ticket-alt", "color" => "red", "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'"],
        ["title" => "In Progress Tickets", "icon" => "fa-spinner", "color" => "blue", "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'in_progress'"],
        ["title" => "On Hold Tickets", "icon" => "fa-pause-circle", "color" => "yellow", "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'on_hold'"],
        ["title" => "Resolved Tickets", "icon" => "fa-check-circle", "color" => "green", "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'resolved'"],
        ["title" => "Daily Active Users", "icon" => "fa-chart-line", "color" => "indigo", "query" => "SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE DATE(created_at) = CURDATE()"],
        ["title" => "System Load", "icon" => "fa-server", "color" => "gray", "query" => "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size FROM information_schema.tables WHERE table_schema = DATABASE()"]
    ];
}

// Process widgets
foreach ($widgets as &$widget) {
    try {
        $stmt = $pdo->query($widget['query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        $widget['count'] = "Error";
        error_log("Widget Error: " . $e->getMessage());
    }
}

// Fetch recent data
$recentData = [
    'activity' => $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC),
    'feedback' => $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
    'tickets' => $pdo->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
    'users' => $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC)
];

// Error log
$errorLogLines = [];
$errorLogPath = realpath(__DIR__ . '/../error_log');
if ($errorLogPath && is_readable($errorLogPath)) {
    $lines = file($errorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $errorLogLines = array_slice($lines, -20);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="../assets/js/dashboard.js" defer></script>
    <style>
        :root {
            --primary: #5e64ff;
            --primary-light: #8a90ff;
            --secondary: #f0f4f7;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #fd7e14;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --sidebar-width: 250px;
        }

        /* Frappe/Jinja Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: var(--dark);
            border-color: var(--gray-light);
        }
        
        .btn-secondary:hover {
            background-color: var(--gray-light);
            transform: translateY(-1px);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Dashboard Layout */
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-main {
            flex: 1;
            padding: 1.5rem;
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            background-color: #f9fafb;
            min-width: 0;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Widget Grid */
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
        }
        
        .dashboard-widget {
            background: var(--white);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
            border: 1px solid var(--gray-light);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .dashboard-widget:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .dashboard-widget i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .dashboard-widget h3 {
            font-size: 1.75rem;
            margin: 0.5rem 0;
            color: var(--dark);
        }
        
        .dashboard-widget p {
            margin: 0;
            color: var(--gray);
            font-size: 0.875rem;
        }

        /* Dashboard Sections */
        .dashboard-section {
            background: var(--white);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--gray-light);
        }
        
        .dashboard-section h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            color: var(--dark);
        }

        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .table th {
            background-color: var(--secondary);
            font-weight: 500;
        }

        /* Quick Links */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
        }
        
        .quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: var(--white);
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.2s;
            border: 1px solid var(--gray-light);
            text-align: center;
        }
        
        .quick-link:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .quick-link i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .quick-link span {
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .widget-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .chart-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .widget-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }
            
            .quick-links {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
        
        @media (max-width: 576px) {
            .widget-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .quick-links {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            
            .dashboard-widget {
                padding: 1rem;
            }
            
            .dashboard-widget i {
                font-size: 1.5rem;
            }
            
            .dashboard-widget h3 {
                font-size: 1.5rem;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php
        $ADMIN_UNREAD_MESSAGES = $unreadMessagesCount;
        include __DIR__ . '/includes/admin_sidebar.php';
        ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="d-flex gap-2">
                    <?php if ($unreadMessagesCount > 0): ?>
                        <a href="messages.php" class="btn btn-primary" style="position:relative;">
                            <i class="fas fa-envelope"></i>
                            <span class="badge"><?= $unreadMessagesCount ?></span>
                            Messages
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </header>
            
            <div class="content">
                <!-- Quick Links -->
                <div class="quick-links">
                    <a href="users.php" class="quick-link"><i class="fas fa-users"></i><span>Users</span></a>
                    <a href="courses.php" class="quick-link"><i class="fas fa-book"></i><span>Courses</span></a>
                    <a href="enrollments.php" class="quick-link"><i class="fas fa-user-graduate"></i><span>Enrollments</span></a>
                    <a href="feedback.php" class="quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                    <a href="tickets.php" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Tickets</span></a>
                    <a href="reports.php" class="quick-link"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
                    <a href="settings.php" class="quick-link"><i class="fas fa-cog"></i><span>Settings</span></a>
                </div>

                <!-- Widget Grid -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3><?= htmlspecialchars($widget['count']) ?></h3>
                            <p><?= htmlspecialchars($widget['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Chart Row 1 -->
                <div class="chart-row">
                    <div class="dashboard-section">
                        <h2>User Growth</h2>
                        <div class="chart-container">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <h2>Activity Distribution</h2>
                        <div class="chart-container">
                            <canvas id="activityDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Chart Row 2 -->
                <div class="chart-row">
                    <div class="dashboard-section">
                        <h2>Feedback Ratings</h2>
                        <div class="chart-container">
                            <canvas id="feedbackRatingsChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <h2>Ticket Status</h2>
                        <div class="chart-container">
                            <canvas id="ticketStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Chart Row 3 -->
                <div class="chart-row">
                    <div class="dashboard-section">
                        <h2>Course Enrollments</h2>
                        <div class="chart-container">
                            <canvas id="courseEnrollmentChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <h2>System Performance</h2>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0">Recent Activity</h2>
                        <a href="activity.php" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Activity</th>
                                    <th>Details</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentData['activity'] as $activity): ?>
                                <tr>
                                    <td><?= htmlspecialchars($activity['user_id']) ?></td>
                                    <td><?= htmlspecialchars($activity['activity_type']) ?></td>
                                    <td><?= htmlspecialchars(substr($activity['description'], 0, 50)) ?>...</td>
                                    <td><?= htmlspecialchars($activity['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Tickets & Feedback -->
                <div class="chart-row">
                    <div class="dashboard-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="mb-0">Recent Tickets</h2>
                            <a href="tickets.php" class="btn btn-sm btn-secondary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentData['tickets'] as $ticket): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ticket['id']) ?></td>
                                        <td><?= htmlspecialchars(substr($ticket['subject'], 0, 30)) ?>...</td>
                                        <td><span class="badge"><?= htmlspecialchars($ticket['status']) ?></span></td>
                                        <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="mb-0">Recent Feedback</h2>
                            <a href="feedback.php" class="btn btn-sm btn-secondary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentData['feedback'] as $feedback): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($feedback['user_id']) ?></td>
                                        <td><?= str_repeat('★', $feedback['rating']) . str_repeat('☆', 5 - $feedback['rating']) ?></td>
                                        <td><?= htmlspecialchars(substr($feedback['message'], 0, 30)) ?>...</td>
                                        <td><?= htmlspecialchars($feedback['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="dashboard-section">
                    <h2>System Information</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <h3>Server</h3>
                            <ul>
                                <li>PHP Version: <?= $stats['performance']['php_version'] ?></li>
                                <li>Server Software: <?= $stats['performance']['server_software'] ?></li>
                                <li>Server Load: <?= round($stats['performance']['server_load'], 2) ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h3>Database</h3>
                            <ul>
                                <li>Version: <?= $stats['performance']['db_version'] ?></li>
                                <li>Size: <?= $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn() ?> MB</li>
                                <li>Tables: <?= count($stats['tables']) ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart');
        if (userGrowthCtx) {
            const labels = <?= json_encode(array_column($stats['user_growth'], 'date')) ?>;
            const data = <?= json_encode(array_column($stats['user_growth'], 'count')) ?>;
            
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'New Users',
                        data: data,
                        borderColor: '#5e64ff',
                        backgroundColor: 'rgba(94, 100, 255, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Activity Distribution Chart
        const activityDistCtx = document.getElementById('activityDistributionChart');
        if (activityDistCtx) {
            const labels = <?= json_encode(array_column($stats['activity_trends'], 'activity_type')) ?>;
            const data = <?= json_encode(array_column($stats['activity_trends'], 'count')) ?>;
            
            new Chart(activityDistCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Activities',
                        data: data,
                        backgroundColor: '#5e64ff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Feedback Ratings Chart
        const feedbackRatingsCtx = document.getElementById('feedbackRatingsChart');
        if (feedbackRatingsCtx) {
            const labels = <?= json_encode(array_column($stats['feedback_distribution'], 'rating')) ?>;
            const data = <?= json_encode(array_column($stats['feedback_distribution'], 'count')) ?>;
            
            new Chart(feedbackRatingsCtx, {
                type: 'pie',
                data: {
                    labels: labels.map(r => `${r} Stars`),
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Ticket Status Chart
        const ticketStatusCtx = document.getElementById('ticketStatusChart');
        if (ticketStatusCtx) {
            const labels = <?= json_encode(array_column($stats['ticket_status'], 'status')) ?>;
            const data = <?= json_encode(array_column($stats['ticket_status'], 'count')) ?>;
            
            new Chart(ticketStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#5e64ff', '#ff6384', '#ffce56', '#4bc0c0'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Course Enrollment Chart
        const courseEnrollmentCtx = document.getElementById('courseEnrollmentChart');
        if (courseEnrollmentCtx) {
            const labels = <?= json_encode(array_column($stats['course_enrollments'], 'title')) ?>;
            const data = <?= json_encode(array_column($stats['course_enrollments'], 'enrollments')) ?>;
            
            new Chart(courseEnrollmentCtx, {
                type: 'horizontalBar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Enrollments',
                        data: data,
                        backgroundColor: '#5e64ff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });
        }

        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart');
        if (performanceCtx) {
            new Chart(performanceCtx, {
                type: 'radar',
                data: {
                    labels: ['CPU Load', 'Memory Usage', 'DB Queries', 'Response Time'],
                    datasets: [{
                        label: 'Performance',
                        data: [0.7, 0.5, 0.8, 0.9],
                        backgroundColor: 'rgba(94, 100, 255, 0.2)',
                        borderColor: '#5e64ff',
                        pointBackgroundColor: '#5e64ff',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            angleLines: { display: true },
                            suggestedMin: 0,
                            suggestedMax: 1
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>