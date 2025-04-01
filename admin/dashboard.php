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

// Load dashboard configuration
$dashboardConfigPath = __DIR__ . '../assets/js/dashboard.json';
if (!file_exists($dashboardConfigPath)) {
    $_SESSION['error'] = "Dashboard configuration file not found.";
    header("Location: ../error.php");
    exit();
}

$dashboardConfig = json_decode(file_get_contents($dashboardConfigPath), true);
if (!$dashboardConfig || !isset($dashboardConfig['widgets'])) {
    $_SESSION['error'] = "Invalid dashboard configuration.";
    header("Location: ../error.php");
    exit();
}

// Update widget queries to match database schema
$widgets = [
    [
        'title' => 'Total Surveys',
        'count_query' => "SELECT COUNT(*) FROM surveys",
        'icon' => 'fa-file-alt',
        'color' => 'primary'
    ],
    [
        'title' => 'Active Surveys',
        'count_query' => "SELECT COUNT(*) FROM surveys WHERE status = 2", // 2 = active status ID
        'icon' => 'fa-rocket',
        'color' => 'success'
    ],
    [
        'title' => 'Draft Surveys',
        'count_query' => "SELECT COUNT(*) FROM surveys WHERE status = 1", // 1 = draft status ID
        'icon' => 'fa-pencil-alt',
        'color' => 'warning'
    ],
    [
        'title' => 'Archived Surveys',
        'count_query' => "SELECT COUNT(*) FROM surveys WHERE status = 4", // 4 = archived status ID
        'icon' => 'fa-archive',
        'color' => 'danger'
    ],
    [
        'title' => 'Total Users',
        'count_query' => "SELECT COUNT(*) FROM users",
        'icon' => 'fa-users',
        'color' => 'blue'
    ]
];

$sections = [
    [
        'title' => 'Recent Surveys',
        'query' => "SELECT s.title, ss.label as status, s.created_at 
                    FROM surveys s
                    JOIN survey_statuses ss ON s.status = ss.id
                    ORDER BY s.created_at DESC LIMIT 5",
        'columns' => ['title', 'status', 'created_at']
    ],
    [
        'title' => 'Top Categories',
        'query' => "SELECT sc.name, COUNT(s.id) AS survey_count 
                    FROM survey_categories sc 
                    LEFT JOIN surveys s ON sc.id = s.category_id 
                    GROUP BY sc.name 
                    ORDER BY survey_count DESC LIMIT 5",
        'columns' => ['name', 'survey_count']
    ],
    [
        'title' => 'Recent User Activities',
        'query' => "SELECT a.id, u.username, a.action, a.details, a.created_at 
                    FROM audit_logs a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    ORDER BY a.created_at DESC LIMIT 5",
        'columns' => ['ID', 'User', 'Action', 'Details', 'Timestamp'],
        'link' => "audit_log.php",
        'link_text' => "View Full Activity Log"
    ],
    [
        'title' => 'Recent Feedback',
        'query' => "SELECT f.id, u.username, f.message, f.rating, f.created_at 
                    FROM feedback f 
                    LEFT JOIN users u ON f.user_id = u.id 
                    ORDER BY f.created_at DESC LIMIT 5",
        'columns' => ['ID', 'User', 'Message', 'Rating', 'Date'],
        'link' => "feedback_mgmt.php",
        'link_text' => "View All Feedback"
    ]
];

// Fetch widget counts dynamically with error handling
foreach ($widgets as &$widget) {
    try {
        $stmt = $pdo->query($widget['count_query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Widget query failed: " . $e->getMessage());
        $widget['count'] = "Error";
    }
}

// Fetch section data with error handling
foreach ($sections as &$section) {
    try {
        $stmt = $pdo->query($section['query']);
        $section['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Section query failed: " . $e->getMessage());
        $section['rows'] = [];
    }
}

// Prepare chart data with proper joins
try {
    $stmt = $pdo->query("
        SELECT sc.name AS category, COUNT(s.id) AS survey_count
        FROM survey_categories sc
        LEFT JOIN surveys s ON sc.id = s.category_id
        GROUP BY sc.name
        ORDER BY survey_count DESC
    ");
    $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Chart data query failed: " . $e->getMessage());
    $chartData = [];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <?php include 'includes/alerts.php'; ?>
            </header>
            <div class="content">
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

                <!-- Chart Section -->
                <div class="chart-container">
                    <h2>Survey Categories Overview</h2>
                    <canvas id="surveyChart"></canvas>
                </div>

                <!-- Sections -->
                <?php foreach ($sections as $section): ?>
                    <div class="dashboard-section">
                        <h2><?= htmlspecialchars($section['title']) ?></h2>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <?php foreach ($section['columns'] as $column): ?>
                                            <th><?= htmlspecialchars($column) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($section['rows'])): ?>
                                        <?php foreach ($section['rows'] as $row): ?>
                                            <tr>
                                                <?php foreach ($section['columns'] as $column): ?>
                                                    <td>
                                                        <?php if ($column === 'created_at' && isset($row[$column])): ?>
                                                            <?= date('M j, Y g:i A', strtotime($row[$column])) ?>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($row[$column] ?? 'N/A') ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?= count($section['columns']) ?>">No data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if (isset($section['link'])): ?>
                                <div class="text-right mt-3">
                                    <a href="<?= htmlspecialchars($section['link']) ?>" class="btn btn-sm btn-primary">
                                        <?= htmlspecialchars($section['link_text'] ?? 'View More') ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = <?= json_encode($chartData) ?>;
            const ctx = document.getElementById('surveyChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.map(item => item.category),
                    datasets: [{
                        label: 'Number of Surveys',
                        data: chartData.map(item => item.survey_count),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>