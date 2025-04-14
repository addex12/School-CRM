<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// Handle survey ID parameter
$survey_id = $_GET['survey_id'] ?? null;
if (!$survey_id) {
    $_SESSION['error'] = "Survey ID is required.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey details
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Date filtering
$filter_params = [];
$where_clause = "survey_id = ?";
$filter_params[] = $survey_id;

if (!empty($_GET['start_date'])) {
    $where_clause .= " AND submitted_at >= ?";
    $filter_params[] = $_GET['start_date'];
}

if (!empty($_GET['end_date'])) {
    $where_clause .= " AND submitted_at <= ?";
    $filter_params[] = $_GET['end_date'] . ' 23:59:59';
}

// Pagination setup
$per_page = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Get total responses
$stmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses WHERE $where_clause");
$stmt->execute($filter_params);
$total_responses = $stmt->fetchColumn();
$total_pages = max(1, ceil($total_responses / $per_page));

// Get paginated responses
$stmt = $pdo->prepare("
    SELECT sr.*, u.username, u.email, r.role_name 
    FROM survey_responses sr
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE $where_clause
    ORDER BY submitted_at DESC
    LIMIT ? OFFSET ?
");
$params = array_merge($filter_params, [$per_page, $offset]);
$stmt->execute($params);
$responses = $stmt->fetchAll();

// Prepare analytics data
$analytics = [];
foreach ($fields as $field) {
    $stmt = $pdo->prepare("
        SELECT field_value, COUNT(*) as count 
        FROM response_data 
        WHERE field_id = ?
        GROUP BY field_value
        ORDER BY count DESC
    ");
    $stmt->execute([$field['id']]);
    $analytics[$field['id']] = $stmt->fetchAll();
}

// Prepare chart data
$chart_data = [
    'survey' => $survey,
    'fields' => $fields,
    'analytics' => $analytics,
    'total_responses' => $total_responses
];
$chart_json = json_encode($chart_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Results: <?= htmlspecialchars($survey['title']) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-container {
            height: 400px;
            position: relative;
        }
        .response-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3b82f6;
        }
        .filter-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="admin-main">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?= htmlspecialchars($survey['title']) ?> Results</h1>
                <div class="btn-group">
                    <a href="surveys.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Surveys
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number"><?= $total_responses ?></div>
                        <div class="text-muted">Total Responses</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number"><?= date('M j', strtotime($survey['starts_at'])) ?></div>
                        <div class="text-muted">Start Date</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number"><?= date('M j', strtotime($survey['ends_at'])) ?></div>
                        <div class="text-muted">End Date</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number"><?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></div>
                        <div class="text-muted">Anonymous</div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-box">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="survey_id" value="<?= $survey_id ?>">
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="start_date" 
                               value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>" 
                               placeholder="From Date">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="end_date" 
                               value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>" 
                               placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="results.php?survey_id=<?= $survey_id ?>" 
                           class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Charts Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="dashboard-card">
                        <h4 class="mb-3">Response Trend</h4>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <?php foreach ($fields as $field): ?>
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h4 class="mb-3"><?= htmlspecialchars($field['field_label']) ?></h4>
                        <div class="chart-container">
                            <canvas id="chart-<?= $field['id'] ?>"></canvas>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Responses Table -->
            <div class="dashboard-card">
                <h4 class="mb-3">Individual Responses</h4>
                <?php if ($total_responses > 0): ?>
                <div class="table-responsive response-table">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Respondent</th>
                                <th>Submitted At</th>
                                <?php foreach ($fields as $field): ?>
                                <th><?= htmlspecialchars($field['field_label']) ?></th>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responses as $index => $response): 
                                $answers = json_decode($response['answers'], true);
                            ?>
                            <tr>
                                <td><?= $index + 1 + $offset ?></td>
                                <td>
                                    <?php if ($survey['is_anonymous']): ?>
                                        Anonymous
                                    <?php else: ?>
                                        <?= htmlspecialchars($response['username'] ?? 'N/A') ?>
                                        <?php if ($response['email']): ?>
                                            <br><small><?= htmlspecialchars($response['email']) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y H:i', strtotime($response['submitted_at'])) ?></td>
                                <?php foreach ($fields as $field): ?>
                                <td>
                                    <?php
                                    $value = $answers[$field['id']] ?? 'N/A';
                                    if (is_array($value)) {
                                        echo htmlspecialchars(implode(', ', $value));
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                                <?php endforeach; ?>
                                <td>
                                    <a href="response_view.php?id=<?= $response['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
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
                            <a class="page-link" 
                               href="?survey_id=<?= $survey_id ?>&page=<?= $page - 1 ?>">
                                Previous
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" 
                               href="?survey_id=<?= $survey_id ?>&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="?survey_id=<?= $survey_id ?>&page=<?= $page + 1 ?>">
                                Next
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php else: ?>
                <div class="alert alert-info">
                    No responses found for this survey.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Chart initialization
        const chartData = <?= $chart_json ?>;
        
        // Trend Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Responses',
                    data: [20, 45, 60, chartData.total_responses],
                    borderColor: '#3b82f6',
                    tension: 0.4,
                    fill: true
                }]
            }
        });

        // Field-specific charts
        chartData.fields.forEach(field => {
            const ctx = document.getElementById(`chart-${field.id}`);
            const analytics = chartData.analytics[field.id] || [];
            
            new Chart(ctx, {
                type: field.field_type === 'checkbox' ? 'bar' : 'doughnut',
                data: {
                    labels: analytics.map(item => item.field_value),
                    datasets: [{
                        data: analytics.map(item => item.count),
                        backgroundColor: [
                            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                            '#06b6d4', '#84cc16', '#f97316', '#64748b', '#14b8a6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: field.field_label
                        }
                    }
                }
            });
        });
    </script>

    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>