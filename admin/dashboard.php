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
<<<<<<< HEAD
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars( $pageTitle) ?> | Admin Panel</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="../../assets/css/admin.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="../../assets/js/admin.js"></script>
    <style>
  :root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --info-color: #4895ef;
            --warning-color: #f8961e;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fb;
        }

        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px 30px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e6ed;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .content {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
        }

        /* Widgets */
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .dashboard-widget {
            padding: 20px;
            border-radius: var(--border-radius);
            color: white;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        .dashboard-widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .dashboard-widget i {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .dashboard-widget h3 {
            font-size: 16px;
            font-weight: 500;
            margin: 0 0 5px 0;
        }

        .dashboard-widget p {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .widget-primary { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .widget-success { background: linear-gradient(135deg, var(--success-color), #4cc9f0); }
        .widget-info { background: linear-gradient(135deg, var(--info-color), #4895ef); }
        .widget-warning { background: linear-gradient(135deg, var(--warning-color), #f8961e); }
        .widget-danger { background: linear-gradient(135deg, var(--danger-color), #f72585); }

        /* Sections */
        .dashboard-section {
            margin-bottom: 40px;
        }

        .dashboard-section h2 {
            font-size: 20px;
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2f5;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        .table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
        }

        .table td {
            padding: 12px 15px;
            border-top: 1px solid #e9ecef;
            color: #495057;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }

        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 8px;
        }

        .no-data {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
        }
    </style>
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