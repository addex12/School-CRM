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
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .page-title {
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-section {
            margin-bottom: 40px;
        }

        .dashboard-section h2 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #333;
        }

        .dashboard-section .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .dashboard-section .table th,
        .dashboard-section .table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .dashboard-section .table th {
            background-color: #f4f4f4;
        }

        .dashboard-section .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .dashboard-section .btn:hover {
            background-color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
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
                            <a href="<?= htmlspecialchars($section['link']) ?>" class="btn"><?= htmlspecialchars($section['link_text']) ?></a>
                        <?php else: ?>
                            <p>No data available.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>