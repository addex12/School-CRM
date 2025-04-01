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

$pageTitle = "Admin Dashboard";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Load dashboard configuration
$dashboardConfigPath = __DIR__ . '/dashboard.json';
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

$widgets = $dashboardConfig['widgets'];
$sections = $dashboardConfig['sections'] ?? [];

// Fetch widget counts dynamically
foreach ($widgets as &$widget) {
    try {
        if (empty($widget['count_query'])) {
            throw new Exception("Invalid or empty query for widget: " . htmlspecialchars($widget['title']));
        }
        $stmt = $pdo->query($widget['count_query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        $widget['count'] = "Error"; // Default to "Error" if query fails
        error_log("Widget Error: " . $e->getMessage());
    }
}

// Fetch section data dynamically
foreach ($sections as &$section) {
    try {
        if (empty($section['query'])) {
            throw new Exception("Invalid or empty query for section: " . htmlspecialchars($section['title']));
        }
        $stmt = $pdo->query($section['query']);
        $section['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $section['rows'] = []; // Default to an empty array if query fails
        error_log("Section Error: " . $e->getMessage());
    }
}

// Prepare chart data
$chartData = [];
try {
    $stmt = $pdo->query("
        SELECT sc.name AS category, COUNT(s.id) AS survey_count
        FROM survey_categories sc
        LEFT JOIN surveys s ON sc.id = s.category_id
        GROUP BY sc.name
        ORDER BY survey_count DESC
    ");
    $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Chart Data Error: " . $e->getMessage());
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
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
                    <canvas id="surveyChart" data-chart='<?= json_encode($chartData) ?>'></canvas>
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
                                                    <td><?= htmlspecialchars($row[$column] ?? 'N/A') ?></td>
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
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>