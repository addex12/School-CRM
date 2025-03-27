<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Admin Dashboard";

// Load dashboard configuration
$dashboardConfig = json_decode(file_get_contents(__DIR__ . '/dashboard.json'), true);

// Fetch widget data
$widgets = [];
foreach ($dashboardConfig['widgets'] as $widget) {
    $stmt = $pdo->query($widget['query']);
    $value = $stmt->fetchColumn();
    $widgets[] = [
        'title' => $widget['title'],
        'value' => $value,
        'icon' => $widget['icon'],
        'color' => $widget['color']
    ];
}

// Fetch section data
$sections = [];
foreach ($dashboardConfig['sections'] as $section) {
    $stmt = $pdo->query($section['query']);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sections[] = [
        'title' => $section['title'],
        'columns' => $section['columns'],
        'fields' => $section['fields'],
        'data' => $data,
        'link' => $section['link'],
        'link_text' => $section['link_text']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - Admin Panel</title>
        <link rel="stylesheet" href="../../assets/css/admin.css">
        <link rel="stylesheet" href="../../assets/css/responsive.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <!-- Include the new admin.js file -->
        <script src="../../assets/js/admin.js"></script>
    </head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="header-actions">
                    <a href="#" class="btn"><i class="fas fa-bell"></i></a>
                    <a href="#" class="btn"><i class="fas fa-question-circle"></i></a>
                </div>
            </header>

            <div class="content">
                <!-- Widgets Section -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                            <i class="<?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3><?= htmlspecialchars($widget['title']) ?></h3>
                            <p><?= htmlspecialchars($widget['value']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sections -->
                <?php foreach ($sections as $section): ?>
                    <div class="dashboard-section">
                        <h2><?= htmlspecialchars($section['title']) ?></h2>
                        
                        <?php if (count($section['data']) > 0): ?>
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
                                        <?php foreach ($section['data'] as $row): ?>
                                            <tr>
                                                <?php foreach ($section['fields'] as $field): ?>
                                                    <td><?= htmlspecialchars($row[$field] ?? 'N/A') ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="<?= htmlspecialchars($section['link']) ?>" class="btn">
                                <i class="fas fa-arrow-right"></i>
                                <?= htmlspecialchars($section['link_text']) ?>
                            </a>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Add interactivity to the sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const menuGroups = document.querySelectorAll('.menu-group');
            
            menuGroups.forEach(group => {
                const title = group.querySelector('.group-title');
                title.addEventListener('click', () => {
                    group.classList.toggle('active');
                });
            });
        });
    </script>
</body>
</html>