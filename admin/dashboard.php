<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Dashboard";

// Fetch data for charts
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalSurveys = $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();

$monthlySalesData = $pdo->query("
    SELECT MONTHNAME(created_at) AS month, SUM(amount) AS total
    FROM sales
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
")->fetchAll(PDO::FETCH_ASSOC);

$expenseDistribution = $pdo->query("
    SELECT category, SUM(amount) AS total
    FROM expenses
    GROUP BY category
")->fetchAll(PDO::FETCH_ASSOC);

$quarterlyRevenue = $pdo->query("
    SELECT CONCAT('Q', QUARTER(created_at)) AS quarter, SUM(amount) AS total
    FROM sales
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY QUARTER(created_at)
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background: #f4f6fa;
            margin: 0;
            padding: 0;
        }
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
        }
        .admin-main {
            flex: 1;
            padding: 1.5rem;
            margin-left: 240px;
            transition: margin-left 0.2s;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .dashboard-header h1 {
            font-size: 1.5rem;
            color: #34495e;
        }
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            text-align: center;
        }
        .card h3 {
            font-size: 1.2rem;
            color: #34495e;
            margin: 0.5rem 0;
        }
        .card p {
            font-size: 0.9rem;
            color: #757575;
        }
        .chart-container {
            margin-top: 2rem;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        .chart-container h3 {
            font-size: 1.2rem;
            color: #34495e;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="dashboard-header">
                <h1>Dashboard</h1>
            </div>
            <div class="cards-container">
                <div class="card">
                    <i class="fas fa-users fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Users</h3>
                    <p><?= $totalUsers ?></p>
                </div>
                <div class="card">
                    <i class="fas fa-chalkboard-teacher fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Teachers</h3>
                    <p><?= $totalTeachers ?></p>
                </div>
                <div class="card">
                    <i class="fas fa-user-graduate fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Students</h3>
                    <p><?= $totalStudents ?></p>
                </div>
                <div class="card">
                    <i class="fas fa-poll fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Surveys</h3>
                    <p><?= $totalSurveys ?></p>
                </div>
            </div>
            <div class="chart-container">
                <h3>Monthly Sales Data</h3>
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Expense Distribution</h3>
                <canvas id="pieChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Quarterly Revenue Trends</h3>
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>
    <script>
        const barChartData = {
            labels: <?= json_encode(array_column($monthlySalesData, 'month')) ?>,
            datasets: [{
                label: 'Sales ($)',
                data: <?= json_encode(array_column($monthlySalesData, 'total')) ?>,
                backgroundColor: '#2e8bff'
            }]
        };

        const pieChartData = {
            labels: <?= json_encode(array_column($expenseDistribution, 'category')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($expenseDistribution, 'total')) ?>,
                backgroundColor: ['#2e8bff', '#ff6384', '#ffcd56', '#4bc0c0']
            }]
        };

        const lineChartData = {
            labels: <?= json_encode(array_column($quarterlyRevenue, 'quarter')) ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?= json_encode(array_column($quarterlyRevenue, 'total')) ?>,
                borderColor: '#2e8bff',
                fill: false
            }]
        };

        new Chart(document.getElementById('barChart'), { type: 'bar', data: barChartData });
        new Chart(document.getElementById('pieChart'), { type: 'pie', data: pieChartData });
        new Chart(document.getElementById('lineChart'), { type: 'line', data: lineChartData });
    </script>
</body>
</html>
