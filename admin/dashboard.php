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
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Admin Dashboard";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Load widgets from JSON
$widgetsConfig = json_decode(file_get_contents('../assets/config/dashboard.json'), true);
$widgets = $widgetsConfig['widgets'];

foreach ($widgets as &$widget) {
    try {
        $stmt = $pdo->query($widget['count_query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        $widget['count'] = "Error";
        error_log("Widget Error: " . $e->getMessage());
    }
}

// Fetch recent activity log
$activityLog = [];
try {
    // First check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'activity_log'")->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->query("
            SELECT al.*, COALESCE(u.username, 'System') as username 
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC 
            LIMIT 10
        ");
        $activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("Activity Log table doesn't exist");
        $activityLog = [['error' => 'Table not available']];
    }
} catch (Exception $e) {
    error_log("Activity Log Error: " . $e->getMessage());
    $activityLog = [['error' => 'Failed to load data']];
}

// Fetch recent feedback
$feedback = [];
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'feedback'")->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->query("
            SELECT f.*, COALESCE(u.username, 'Anonymous') as username 
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.id
            ORDER BY f.created_at DESC 
            LIMIT 5
        ");
        $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("Feedback table doesn't exist");
        $feedback = [['error' => 'Table not available']];
    }
} catch (Exception $e) {
    error_log("Feedback Error: " . $e->getMessage());
    $feedback = [['error' => 'Failed to load data']];
}

// Fetch recent support tickets
$tickets = [];
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'support_tickets'")->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->query("
            SELECT 
                st.*, 
                COALESCE(u.username, 'Unknown') as username,
                COALESCE(tp.label, 'Medium') as priority_label
            FROM support_tickets st
            LEFT JOIN users u ON st.user_id = u.id
            LEFT JOIN ticket_priorities tp ON st.priority_id = tp.id
            ORDER BY st.created_at DESC 
            LIMIT 5
        ");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("Support Tickets table doesn't exist");
        $tickets = [['error' => 'Table not available']];
    }
} catch (Exception $e) {
    error_log("Tickets Error: " . $e->getMessage());
    $tickets = [['error' => 'Failed to load data']];
}

// Fetch data for charts
$chartData = [];
try {
    // User registration chart data (last 7 days)
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at)
    ");
    $chartData['userRegistrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Survey status data
    $stmt = $pdo->query("
        SELECT ss.label, COUNT(s.id) as count 
        FROM surveys s 
        JOIN survey_statuses ss ON s.status_id = ss.id 
        GROUP BY s.status_id
    ");
    $chartData['surveyStatus'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ticket priority data
    $stmt = $pdo->query("
        SELECT tp.label, COUNT(st.id) as count 
        FROM support_tickets st 
        JOIN ticket_priorities tp ON st.priority_id = tp.id 
        GROUP BY st.priority_id
    ");
    $chartData['ticketPriority'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Completed vs pending surveys
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status_id = (SELECT id FROM survey_statuses WHERE status = 'active') THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status_id = (SELECT id FROM survey_statuses WHERE status = 'draft') THEN 1 ELSE 0 END) as draft_count
        FROM surveys
    ");
    $surveyCounts = $stmt->fetch(PDO::FETCH_ASSOC);
    $chartData['surveyCounts'] = $surveyCounts;
    
} catch (Exception $e) {
    error_log("Chart Data Error: " . $e->getMessage());
}

// Add survey counts to widgets
foreach ($widgets as &$widget) {
    if ($widget['title'] === 'Completed Surveys') {
        $widget['count'] = $chartData['surveyCounts']['active_count'] ?? 0;
    } elseif ($widget['title'] === 'Pending Surveys') {
        $widget['count'] = $chartData['surveyCounts']['draft_count'] ?? 0;
    }
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
    <script src="../assets/js/charts.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                </div>
                <div class="header-right">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
<!-- In dashboard.php, replace the notifications div with this: -->
    <div class="notifications-toggle">
        <i class="fas fa-bell"></i>
        <span class="badge">3</span>
    </div>
    <div class="notifications-menu">
        <div class="notifications-header">
            <h4>Notifications</h4>
            <a href="notifications.php">View All</a>
        </div>
        <div class="notifications-list">
            <?php
            // Fetch recent notifications
            try {
                $stmt = $pdo->query("SELECT * FROM notifications WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC LIMIT 5");
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($notifications)) {
                    foreach ($notifications as $notification) {
                        echo '<div class="notification-item ' . ($notification['is_read'] ? 'read' : 'unread') . '">';
                        echo '<div class="notification-icon"><i class="fas fa-bell"></i></div>';
                        echo '<div class="notification-content">';
                        echo '<p>' . htmlspecialchars($notification['message']) . '</p>';
                        echo '<small>' . formatDate($notification['created_at']) . '</small>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-notifications">No new notifications</div>';
                }
            } catch (Exception $e) {
                error_log("Notifications Error: " . $e->getMessage());
                echo '<div class="no-notifications">Error loading notifications</div>';
            }
            ?>
        </div>
    </div>
</div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <!-- Summary Widgets Section -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                            <div class="widget-icon">
                                <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            </div>
                            <div class="widget-content">
                                <h3><?= htmlspecialchars($widget['count']) ?></h3>
                                <p><?= htmlspecialchars($widget['title']) ?></p>
                            </div>
                            <div class="widget-action">
                                <a href="<?= htmlspecialchars($widget['link'] ?? '#') ?>">View All <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Charts Section -->
                <div class="dashboard-section">
                    <div class="chart-container">
                        <div class="chart-card">
                            <h3>User Registrations (Last 7 Days)</h3>
                            <canvas id="userRegistrationsChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Survey Status Distribution</h3>
                            <canvas id="surveyStatusChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h3>Ticket Priority Distribution</h3>
                            <canvas id="ticketPriorityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Activity Log Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Recent Activity Log</h2>
                        <a href="activity_log.php" class="view-all">View All</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Activity Type</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activityLog)): ?>
                                    <?php foreach ($activityLog as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['id']) ?></td>
                                            <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                                            <td><span class="badge badge-activity"><?= htmlspecialchars($log['activity_type']) ?></span></td>
                                            <td><?= htmlspecialchars($log['description']) ?></td>
                                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                            <td><?= formatDate(htmlspecialchars($log['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">No recent activity found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Items Section -->
                <div class="recent-items-grid">
                    <!-- Feedback Section -->
                    <div class="recent-item-card">
                        <div class="section-header">
                            <h2><i class="fas fa-comment-dots"></i> Recent Feedback</h2>
                            <a href="feedback.php" class="view-all">View All</a>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Subject</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($feedback)): ?>
                                        <?php foreach ($feedback as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['username'] ?? 'Anonymous') ?></td>
                                                <td><?= htmlspecialchars($item['subject']) ?></td>
                                                <td>
                                                    <div class="rating-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?= $i <= $item['rating'] ? 'filled' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td><span class="badge badge-<?= str_replace('_', '-', $item['status']) ?>"><?= ucwords(str_replace('_', ' ', $item['status'])) ?></span></td>
                                                <td><?= formatDate(htmlspecialchars($item['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="no-data">No feedback found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Support Tickets Section -->
                    <div class="recent-item-card">
                        <div class="section-header">
                            <h2><i class="fas fa-ticket-alt"></i> Recent Support Tickets</h2>
                            <a href="tickets.php" class="view-all">View All</a>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ticket #</th>
                                        <th>User</th>
                                        <th>Subject</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($tickets)): ?>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ticket['ticket_number']) ?></td>
                                                <td><?= htmlspecialchars($ticket['username']) ?></td>
                                                <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                                <td><span class="badge badge-priority"><?= htmlspecialchars($ticket['priority_label']) ?></span></td>
                                                <td><span class="badge badge-<?= str_replace('_', '-', $ticket['status']) ?>"><?= ucwords(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                                                <td><?= formatDate(htmlspecialchars($ticket['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="no-data">No tickets found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Pass PHP data to JavaScript
        const chartData = <?= json_encode($chartData) ?>;
        const activityLog = <?= json_encode($activityLog) ?>;    
        const feedback = <?= json_encode($feedback) ?>;    
        const tickets = <?= json_encode($tickets) ?>;   
    </script>
</body>
</html>