<?php
// admin/all_responses.php
// Show all survey responses for admin, with no filtering by survey_id
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

$pageTitle = 'All Survey Responses';

// Pagination setup
$per_page = 30;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]));
$offset = ($page - 1) * $per_page;

// Get total responses count
$total_stmt = $pdo->query("SELECT COUNT(*) FROM survey_responses");
$total_responses = $total_stmt->fetchColumn();
$total_pages = max(1, ceil($total_responses / $per_page));

// Get paginated responses (with survey and user info)
$response_stmt = $pdo->prepare("
    SELECT sr.*, s.title AS survey_title, u.username, u.email, r.role_name
    FROM survey_responses sr
    LEFT JOIN surveys s ON sr.survey_id = s.id
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY sr.submitted_at DESC
    LIMIT ? OFFSET ?
");
$response_stmt->execute([$per_page, $offset]);
$responses = $response_stmt->fetchAll();

// --- Analytics Data for Charts ---
// 1. Responses per survey (bar)
$survey_counts_stmt = $pdo->query("SELECT s.title AS survey_title, COUNT(*) AS count FROM survey_responses sr LEFT JOIN surveys s ON sr.survey_id = s.id GROUP BY sr.survey_id ORDER BY count DESC");
$survey_counts = $survey_counts_stmt->fetchAll();

// 2. Responses per day (line)
$date_counts_stmt = $pdo->query("SELECT DATE(sr.submitted_at) as date, COUNT(*) as count FROM survey_responses sr GROUP BY DATE(sr.submitted_at) ORDER BY date ASC");
$date_counts = $date_counts_stmt->fetchAll();

// 3. Responses by role (pie)
$role_counts_stmt = $pdo->query("SELECT r.role_name, COUNT(*) as count FROM survey_responses sr LEFT JOIN users u ON sr.user_id = u.id LEFT JOIN roles r ON u.role_id = r.id GROUP BY r.role_name");
$role_counts = $role_counts_stmt->fetchAll();

$chart_data = [
    'surveys' => $survey_counts,
    'dates' => $date_counts,
    'roles' => $role_counts
];
$chart_json = json_encode($chart_data);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .response-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .response-table th, .response-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .response-table th { background: #f8f9fa; }
        .pagination { margin: 30px 0 0 0; }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-header">
            <h1>All Survey Responses</h1>
        </header>
        <!-- Chart Analytics Section -->
        <div class="row" style="margin-bottom:30px;">
            <div class="col-md-6">
                <div class="chart-container" style="background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:20px;">
                    <h3 style="font-size:1.1rem;">Responses per Survey</h3>
                    <canvas id="surveyBarChart" height="130"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container" style="background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:20px;">
                    <h3 style="font-size:1.1rem;">Responses Over Time</h3>
                    <canvas id="dateLineChart" height="130"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container" style="background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:20px;">
                    <h3 style="font-size:1.1rem;">Responses by Role</h3>
                    <canvas id="rolePieChart" height="130"></canvas>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="response-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Survey</th>
                        <th>Respondent</th>
                        <th>Role</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($responses as $index => $response): ?>
                    <tr>
                        <td><?= $index + 1 + $offset ?></td>
                        <td><?= htmlspecialchars($response['survey_title'] ?? 'Unknown') ?></td>
                        <td><?= $response['username'] ? htmlspecialchars($response['username']) : '<span class="text-muted">Anonymous</span>' ?></td>
                        <td><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></td>
                        <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                        <td>
                            <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php require_once 'includes/footer.php'; ?>
    <script>
    const chartData = <?= $chart_json ?>;
    document.addEventListener('DOMContentLoaded', function() {
        // Bar Chart: Responses per Survey
        if (chartData.surveys && chartData.surveys.length > 0) {
            const ctx1 = document.getElementById('surveyBarChart').getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: chartData.surveys.map(s => s.survey_title),
                    datasets: [{
                        label: 'Responses',
                        data: chartData.surveys.map(s => s.count),
                        backgroundColor: '#4361ee',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: false },
                        datalabels: { anchor: 'end', align: 'top', color: '#4361ee', font: { weight: 'bold' } }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false } }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
        // Line Chart: Responses Over Time
        if (chartData.dates && chartData.dates.length > 0) {
            const ctx2 = document.getElementById('dateLineChart').getContext('2d');
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: chartData.dates.map(d => d.date),
                    datasets: [{
                        label: 'Responses',
                        data: chartData.dates.map(d => d.count),
                        backgroundColor: 'rgba(67,97,238,0.15)',
                        borderColor: '#4361ee',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#4361ee',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: false },
                        datalabels: { anchor: 'end', align: 'top', color: '#4361ee', font: { weight: 'bold' } }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Responses' }, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { title: { display: false }, grid: { display: false } }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
        // Pie Chart: Responses by Role
        if (chartData.roles && chartData.roles.length > 0) {
            const ctx3 = document.getElementById('rolePieChart').getContext('2d');
            new Chart(ctx3, {
                type: 'pie',
                data: {
                    labels: chartData.roles.map(r => r.role_name || 'Unknown'),
                    datasets: [{
                        data: chartData.roles.map(r => r.count),
                        backgroundColor: ['#4361ee', '#4895ef', '#4cc9f0', '#b5179e', '#f72585', '#7209b7', '#3f37c9'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: false },
                        datalabels: {
                            formatter: (value, ctx) => {
                                const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                return (value/total*100).toFixed(1) + '%';
                            },
                            color: '#fff',
                            font: { weight: 'bold' }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    });
    </script>
</body>
</html>
