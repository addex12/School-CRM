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

// Load dashboard configuration
$dashboardConfig = json_decode(file_get_contents(__DIR__ . '/dashboard.json'), true);

if (!$dashboardConfig || !isset($dashboardConfig['widgets'])) {
    die("Invalid dashboard configuration.");
}

$widgets = $dashboardConfig['widgets'];
$sections = $dashboardConfig['sections'] ?? [];

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars( $pageTitle) ?> | Admin Panel</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="../../assets/css/admin.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="../../assets/js/dashboard.js" defer></script>
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

        .chart-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .feedback-summary {
            margin-bottom: 20px;
        }
        .feedback-summary p {
            font-size: 16px;
            margin: 5px 0;
        }
        .fa-star {
            font-size: 18px;
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
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>" data-query="<?= htmlspecialchars($widget['count_query']) ?>">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3 class="widget-count">Loading...</h3>
                            <p><?= htmlspecialchars($widget['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sections -->
                <?php foreach ($sections as $section): ?>
                    <div class="dashboard-section">
                        <h2><?= htmlspecialchars($section['title']) ?></h2>
                        <div class="table-container">
                            <table class="table" data-query="<?= htmlspecialchars($section['query']) ?>">
                                <thead>
                                    <tr>
                                        <?php foreach ($section['columns'] as $column): ?>
                                            <th><?= htmlspecialchars($column) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                    <tr><td colspan="<?= count($section['columns']) ?>">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <a href="<?= htmlspecialchars($section['link']) ?>" class="btn">
                            <i class="fas fa-arrow-right"></i>
                            <?= htmlspecialchars($section['link_text']) ?>
                        </a>
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

        // Render feedback chart
        const ctx = document.getElementById('feedbackChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($ratings) ?>,
                datasets: [{
                    label: 'Feedback Count',
                    data: <?= json_encode($counts) ?>,
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336', '#2196f3', '#9c27b0'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const percentage = ((context.raw / <?= $totalFeedback ?>) * 100).toFixed(1);
                                return `${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: { title: { display: true, text: 'Ratings' } },
                    y: { title: { display: true, text: 'Count' }, beginAtZero: true }
                }
            }
        });
    </script>
            <?php include 'includes/footer.php';?>
            </body>
</html>