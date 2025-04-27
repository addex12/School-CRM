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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --primary-color: #2e8bff;
            --secondary-color: #e0e0e0;
            --text-muted: #757575;
            --border-radius: 4px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* ERPNext Layout System */
        .admin-dashboard {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .admin-main {
            padding: 1.5rem;
            transition: margin 0.3s ease;
            position: relative;
        }

        /* Mobile First Approach */
        @media (max-width: 768px) {
            .admin-dashboard {
                grid-template-columns: 1fr;
                position: relative;
            }

            .admin-sidebar {
                position: fixed;
                left: -240px;
                top: 0;
                bottom: 0;
                z-index: 1000;
                transition: left 0.3s ease;
                background: #fff;
                box-shadow: 2px 0 8px rgba(0,0,0,0.1);
            }

            .sidebar-open .admin-sidebar {
                left: 0;
            }

            .admin-main {
                padding: 1rem;
                margin-left: 0 !important;
            }
        }

        /* ERPNext Button System */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid transparent;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: #333;
            border-color: #d1d1d1;
        }

        .btn:hover {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        /* Card System */
        .dashboard-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        /* Responsive Grid */
        .widget-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        }

        .dashboard-widget {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border-radius: var(--border-radius);
            background: #fff;
            border: 1px solid #e0e0e0;
        }

        .dashboard-widget i {
            font-size: 1.25rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        /* Mobile Optimization */
        @media (max-width: 480px) {
            .widget-grid {
                grid-template-columns: 1fr;
            }

            .quick-links {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-section canvas {
                max-height: 250px !important;
            }
        }

        /* Hamburger Menu */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: var(--border-radius);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
        }

        /* Chart Responsiveness */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
    </style>
</head>

<body>
    <button class="mobile-menu-toggle btn btn-primary">
        <i class="fas fa-bars"></i>
    </button>

    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            
            <!-- Existing Content - Restructured for Mobile -->
            <div class="content">
                <div class="dashboard-widgets-and-links">
                    <div class="widget-grid">
                        <?php foreach ($widgets as $widget): ?>
                            <div class="dashboard-widget">
                                <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                                <h3><?= htmlspecialchars($widget['count']) ?></h3>
                                <p class="text-muted"><?= htmlspecialchars($widget['title']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="quick-links grid-container">
                        <!-- Existing quick links... -->
                    </div>
                </div>

                <!-- Charts Section with Responsive Containers -->
                <?php foreach ($chartSections as $section): ?>
                    <div class="dashboard-section">
                        <h2><?= $section['title'] ?></h2>
                        <div class="chart-container">
                            <canvas id="<?= $section['id'] ?>"></canvas>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- System Health Section -->
                <div class="dashboard-section">
                    <!-- Existing system health content... -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Toggle
        document.querySelector('.mobile-menu-toggle').addEventListener('click', () => {
            document.querySelector('.admin-dashboard').classList.toggle('sidebar-open');
        });

        // Dynamic Chart Resizing
        window.addEventListener('resize', () => {
            charts.forEach(chart => chart.resize());
        });

        // Initialize Charts with ERPNext Color Scheme
        const chartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 20
                    }
                }
            }
        };

        // Initialize all charts with these options...
    </script>
</body>
</html>