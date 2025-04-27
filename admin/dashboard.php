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
        // Ensure no duplicate widgets are added
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/css/brands.min.css">
    <link rel="stylesheet" href="../assets/css/solid.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
    <style>
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            background: #f4f6fa;
        }
        .admin-main {
            flex: 1;
            padding: 2rem 2.5rem;
        }
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .users-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .users-header .btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s;
            text-decoration: none;
        }
        .users-header .btn:hover {
            background: #217dbb;
        }
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin: 0 auto; /* Center the grid horizontally */
        }

        .dashboard-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
            position: relative;
            margin: 0 auto; /* Center the widget horizontally */
        }

        .dashboard-widget i {
            font-size: 2.2rem;
            margin-bottom: 0.7rem;
            color: #f1c40f;
        }
        .widget-blue { border-top: 4px solid #3498db; }
        .widget-green { border-top: 4px solid #27ae60; }
        .widget-orange { border-top: 4px solid #f39c12; }
        .widget-red { border-top: 4px solid #e74c3c; }
        .widget-purple { border-top: 4px solid #8e44ad; }
        .widget-teal { border-top: 4px solid #16a085; }
        .widget-yellow { border-top: 4px solid #f1c40f; }
        .dashboard-widget h3 {
            font-size: 2.1rem;
            margin: 0.5rem 0 0.2rem 0;
            color: #2c3e50;
        }
        .dashboard-widget p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin: 0;
        }
        .dashboard-section {
            margin-bottom: 2.5rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
        }
        .dashboard-section h2 {
            font-size: 1.3rem;
            color: #34495e;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid #f0f2f5;
            padding-bottom: 0.5rem;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        tr:hover {
            background: #f4f8fb;
        }
        .dashboard-section pre.error-log {
            background: #222;
            color: #f1c40f;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .quick-links {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .quick-link {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.2rem 1.5rem;
            text-align: center;
            min-width: 140px;
            transition: box-shadow 0.15s;
        }
        .quick-link:hover {
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
        }
        .quick-link i {
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
            color: #3498db;
        }
        .quick-link span {
            display: block;
            margin-top: 0.3rem;
            color: #34495e;
            font-weight: 500;
        }
        @media (max-width: 900px) {
            .widget-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-section {
                padding: 1rem 0.5rem;
            }
        }
        @media (max-width: 600px) {
            .admin-main {
                padding: 10px 2px 80px;
            }
            .dashboard-widget, .dashboard-section {
                padding: 1rem 0.5rem;
            }
            th, td {
                padding: 8px 6px;
            }
            .widget-grid {
                grid-template-columns: 1fr; /* Stack widgets vertically on small screens */
                gap: 1rem; /* Reduce gap between widgets */
            }

            .dashboard-widget {
                width: 95%; /* Adjust widget width for small screens */
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php
        // Make unreadMessagesCount available to sidebar
        $ADMIN_UNREAD_MESSAGES = $unreadMessagesCount;
        include __DIR__ . '/includes/admin_sidebar.php';
        ?>
        <div class="admin-main">
            <header class="admin-header" style="display: flex; align-items: center; justify-content: space-between;">
                <h1 style="margin:0;"><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($unreadMessagesCount > 0): ?>
                    <a href="messages.php" class="erpnext-btn btn-secondary" style="position:relative;">
                        <i class="fas fa-envelope"></i>
                        <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:#fff;border-radius:50%;padding:2px 7px;font-size:0.85em;font-weight:600;">
                            <?= $unreadMessagesCount ?>
                        </span>
                        New Messages
                    </a>
                <?php endif; ?>
            </header>
            <div class="content">

                <!-- Quick Links Section -->
                <div class="quick-links">
                    <a href="users.php" class="quick-link"><i class="fas fa-users"></i><span>Manage Users</span></a>
                    <!--<a href="students.php" class="quick-link"><i class="fas fa-user-graduate"></i><span>Students</span></a>
                    <a href="teachers.php" class="quick-link"><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></a>
                    <a href="classes.php" class="quick-link"><i class="fas fa-school"></i><span>Classes</span></a>
                    <a href="curriculums.php" class="quick-link"><i class="fas fa-list"></i><span>Curriculums</span></a>
                    <a href="sections.php" class="quick-link"><i class="fas fa-th-large"></i><span>Sections</span></a>
                    <a href="subjects.php" class="quick-link"><i class="fas fa-book"></i><span>Subjects</span></a>
                    <a href="grading_scales.php" class="quick-link"><i class="fas fa-chart-line"></i><span>Grading Scales</span></a>
                    <a href="grades.php" class="quick-link"><i class="fas fa-file-alt"></i><span>Grades</span></a> -->
                    <a href="surveys.php" class="quick-link"><i class="fas fa-poll"></i><span>Surveys</span></a>
                    <a href="feedback.php" class="quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                    <a href="support_tickets.php" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
                    <a href="events.php" class="quick-link"><i class="fas fa-calendar-plus"></i><span>Add Event</span></a>
                </div>

                <!-- Widgets Section -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3><?= htmlspecialchars($widget['count']) ?></h3>
                            <p><?= htmlspecialchars($widget['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Survey Participation Chart -->
                <div class="dashboard-section">
                    <h2>Survey Participation</h2>
                    <canvas id="surveyParticipationChart" height="80"></canvas>
                </div>

                <!-- Feedback Ratings Chart -->
                <div class="dashboard-section">
                    <h2>Feedback Ratings</h2>
                    <canvas id="feedbackRatingsChart" height="80"></canvas>
                </div>

                <!-- Support Ticket Status Chart -->
                <div class="dashboard-section">
                    <h2>Support Ticket Status</h2>
                    <canvas id="ticketStatusChart" height="80"></canvas>
                </div>

                <!-- System Stats Section -->
                <div class="dashboard-section">
                    <h2>System Stats</h2>
                    <ul>
                        <li>PHP Version: <?= phpversion() ?></li>
                        <li>Server Software: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></li>
                        <li>Database Host: <?= htmlspecialchars(DB_HOST ?? 'localhost') ?></li>
                        <li>Current Time: <?= date('Y-m-d H:i:s') ?></li>
                    </ul>
                </div>

                <!-- Error Log Section -->
                <div class="dashboard-section">
                    <h2>Recent Error Log</h2>
                    <?php if (!empty($errorLogLines)): ?>
                        <pre class="error-log"><?= htmlspecialchars(implode("\n", $errorLogLines)) ?></pre>
                    <?php else: ?>
                        <p>No recent errors found or error.log not readable.</p>
                    <?php endif; ?>
                </div>

                <!-- Activity Log Section -->
                <div class="dashboard-section">
                    <h2>Recent Activity Log</h2>
                    <div class="table-container">
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
                <div class="dashboard-section">
                    <h2>User Activity Logs</h2>
                    <div class="table-container">
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
                <div class="dashboard-section">
                    <h2>Recent Feedback</h2>
                    <div class="table-container">
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
                <div class="dashboard-section">
                    <h2>Recent Support Tickets</h2>
                    <div class="table-container">
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
        <?php include 'includes/footer.php'; ?>
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
