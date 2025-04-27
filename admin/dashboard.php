<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
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
        .admin-main.collapsed {
            margin-left: 60px;
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
        .dashboard-header .btn-primary {
            background: #2e8bff;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            font-weight: 500;
            transition: box-shadow 0.2s;
        }
        .dashboard-header .btn-primary:hover {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
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
                <button class="btn-primary"><i class="fas fa-plus"></i> Add New</button>
            </div>
            <div class="cards-container">
                <div class="card">
                    <i class="fas fa-users fa-2x" style="color: #2e8bff;"></i>
                    <h3>Total Users</h3>
                    <p>1,234</p>
                </div>
                <div class="card">
                    <i class="fas fa-chart-line fa-2x" style="color: #2e8bff;"></i>
                    <h3>Monthly Sales</h3>
                    <p>$12,345</p>
                </div>
                <div class="card">
                    <i class="fas fa-wallet fa-2x" style="color: #2e8bff;"></i>
                    <h3>Expenses</h3>
                    <p>$4,567</p>
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
        // Mock data for charts
        const barChartData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Sales ($)',
                data: [1200, 1500, 1800, 2000, 2200, 2500, 2700, 3000, 3200, 3500, 3700, 4000],
                backgroundColor: '#2e8bff'
            }]
        };

        const pieChartData = {
            labels: ['Marketing', 'Operations', 'Development', 'Other'],
            datasets: [{
                data: [40, 30, 20, 10],
                backgroundColor: ['#2e8bff', '#ff6384', '#ffcd56', '#4bc0c0']
            }]
        };

        const lineChartData = {
            labels: ['Q1', 'Q2', 'Q3', 'Q4'],
            datasets: [{
                label: 'Revenue ($)',
                data: [5000, 7000, 8000, 10000],
                borderColor: '#2e8bff',
                fill: false
            }]
        };

        // Render charts
        const barChart = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: barChartData
        });

        const pieChart = new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: pieChartData
        });

        const lineChart = new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: lineChartData
        });
    </script>
</body>
</html>
