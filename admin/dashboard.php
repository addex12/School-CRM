<?php
ob_start(); // Start output buffering
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

// Add more widgets for dashboard revamp
$widgets = [
    [
        "title" => "Total Users",
        "icon" => "fa-users",
        "color" => "blue",
        "query" => "SELECT COUNT(*) FROM users"
    ],
    [
        "title" => "Total Students",
        "icon" => "fa-user-graduate",
        "color" => "purple",
        "query" => "SELECT COUNT(*) FROM students"
    ],
    [
        "title" => "Total Teachers",
        "icon" => "fa-chalkboard-teacher",
        "color" => "teal",
        "query" => "SELECT COUNT(*) FROM teachers"
    ],
    [
        "title" => "Total Classes",
        "icon" => "fa-school",
        "color" => "orange",
        "query" => "SELECT COUNT(*) FROM classes"
    ],
    [
        "title" => "Active Surveys",
        "icon" => "fa-poll",
        "color" => "green",
        "query" => "SELECT COUNT(*) FROM surveys WHERE is_active = 1"
    ],
    [
        "title" => "Feedback Received",
        "icon" => "fa-comments",
        "color" => "yellow",
        "query" => "SELECT COUNT(*) FROM feedback"
    ],
    [
        "title" => "Open Tickets",
        "icon" => "fa-ticket-alt",
        "color" => "red",
        "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'"
    ]
];

foreach ($widgets as &$widget) {
    try {
        $stmt = $pdo->query($widget['query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        $widget['count'] = "Error";
        error_log("Widget Error: " . $e->getMessage());
    }
}

// Fetch recent activity log
$activityLog = [];
try {
    $stmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
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
$errorLogPath = realpath(__DIR__ . '/../error.log');
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
            margin-bottom: 2.5rem;
        }
        .dashboard-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
            position: relative;
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
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .dashboard-section h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">

                <!-- Quick Links Section -->
                <div class="quick-links">
                    <a href="users.php" class="quick-link"><i class="fas fa-users"></i><span>Manage Users</span></a>
                    <a href="students.php" class="quick-link"><i class="fas fa-user-graduate"></i><span>Students</span></a>
                    <a href="teachers.php" class="quick-link"><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></a>
                    <a href="classes.php" class="quick-link"><i class="fas fa-school"></i><span>Classes</span></a>
                    <a href="surveys.php" class="quick-link"><i class="fas fa-poll"></i><span>Surveys</span></a>
                    <a href="feedback.php" class="quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                    <a href="support_tickets.php" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
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
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>
