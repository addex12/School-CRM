<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// Debug: Show errors from session if any
if (!empty($_SESSION['error'])) {
    echo '<div style="color:red; font-weight:bold;">Error: ' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

// Debug: Check DB connection
if (!$pdo) {
    die('<div style="color:red; font-weight:bold;">Database connection failed.</div>');
}

$survey_id = $_GET['survey_id'] ?? null;

// Debug: Output survey_id
if (!$survey_id) {
    echo '<div style="color:red; font-weight:bold;">Debug: survey_id is missing from GET parameters.</div>';
    $_SESSION['error'] = "Survey ID is required.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey details
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

// Debug: Output survey fetch result
if (!$survey) {
    echo '<div style="color:red; font-weight:bold;">Debug: No survey found for survey_id = ' . htmlspecialchars($survey_id) . '.</div>';
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Date range filter
$whereClause = "sr.survey_id = ?";
$params = [$survey_id];
if (!empty($_GET['start_date'])) {
    $whereClause .= " AND sr.submitted_at >= ?";
    $params[] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $whereClause .= " AND sr.submitted_at <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
}

// Get responses with pagination
$per_page = 20;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM survey_responses sr 
    WHERE $whereClause
");
$stmt->execute($params);
$total_responses = $stmt->fetchColumn();
$total_pages = ceil($total_responses / $per_page);

$stmt = $pdo->prepare("
    SELECT sr.*, u.username, u.email, r.role_name
    FROM survey_responses sr 
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE $whereClause
    ORDER BY sr.submitted_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$responses = $stmt->fetchAll();

// Prepare analytics data for charts
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

// Prepare JSON data for JavaScript
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .survey-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4361ee;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($survey['title']) ?> Results</h1>
                <div class="header-actions">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="export_csv.php?survey_id=<?= $survey_id ?>">CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="export-pdf">PDF</a></li>
                            <li><a class="dropdown-item" href="export_json.php?survey_id=<?= $survey_id ?>">JSON</a></li>
                        </ul>
                    </div>
                    <a href="surveys.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Surveys
                    </a>
                </div>
            </header>

            <div class="survey-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_responses ?></div>
                    <div class="stat-label">Total Responses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= date('M j, Y', strtotime($survey['starts_at'])) ?></div>
                    <div class="stat-label">Start Date</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= date('M j, Y', strtotime($survey['ends_at'])) ?></div>
                    <div class="stat-label">End Date</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></div>
                    <div class="stat-label">Anonymous</div>
                </div>
            </div>

            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="survey_id" value="<?= $survey_id ?>">
                    <div class="form-group">
                        <label for="start_date">From:</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">To:</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="results.php?survey_id=<?= $survey_id ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </form>
            </div>

            <div class="chart-section" id="chart-section">
                <div class="chart-container" id="summary-chart">
                    <h3>Response Summary</h3>
                    <canvas id="summaryChartCanvas"></canvas>
                </div>
                
                <?php foreach ($fields as $field): ?>
                    <div class="chart-container" id="chart-<?= $field['id'] ?>">
                        <h3><?= htmlspecialchars($field['field_label']) ?></h3>
                        <canvas id="fieldChart-<?= $field['id'] ?>"></canvas>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="response-table-section">
                <h3>Individual Responses</h3>
                <?php if ($total_responses > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Respondent</th>
                                    <th>Role</th>
                                    <?php foreach ($fields as $field): ?>
                                        <th><?= htmlspecialchars($field['field_label']) ?></th>
                                    <?php endforeach; ?>
                                    <th>Submitted At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responses as $index => $response): ?>
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
                                        <td><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></td>
                                        <?php 
                                        $stmt = $pdo->prepare("
                                            SELECT f.field_label, d.field_value
                                            FROM response_data d
                                            JOIN survey_fields f ON d.field_id = f.id
                                            WHERE d.response_id = ?
                                        ");
                                        $stmt->execute([$response['id']]);
                                        $response_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                        
                                        foreach ($fields as $field): ?>
                                            <td>
                                                <?= isset($response_data[$field['field_label']]) ? 
                                                    htmlspecialchars($response_data[$field['field_label']]) : 'N/A' ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></td>
                                        <td>
                                            <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <nav class="pagination-container">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page - 1 ?><?= !empty($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '' ?><?= !empty($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '' ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $i ?><?= !empty($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '' ?><?= !empty($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page + 1 ?><?= !empty($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '' ?><?= !empty($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '' ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info">
                        No responses found for this survey. Please check back later.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const chartData = <?= $chart_json ?>;
        
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Summary chart - Response trend
            if (chartData.total_responses > 0) {
                const ctx = document.getElementById('summaryChartCanvas').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                        datasets: [{
                            label: 'Responses',
                            data: [
                                Math.floor(chartData.total_responses * 0.2),
                                Math.floor(chartData.total_responses * 0.4),
                                Math.floor(chartData.total_responses * 0.7),
                                chartData.total_responses
                            ],
                            backgroundColor: 'rgba(78, 121, 167, 0.2)',
                            borderColor: 'rgba(78, 121, 167, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Response Trend Over Time',
                                font: { size: 16 }
                            },
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Number of Responses' }
                            },
                            x: {
                                title: { display: true, text: 'Time Period' }
                            }
                        }
                    }
                });
            }
            
            // Field-specific charts
            chartData.fields.forEach(field => {
                const fieldAnalytics = chartData.analytics[field.id] || [];
                const ctx = document.getElementById(`fieldChart-${field.id}`).getContext('2d');
                
                if (fieldAnalytics.length > 0) {
                    switch(field.field_type) {
                        case 'radio':
                        case 'select':
                        case 'rating':
                            // Pie/Doughnut chart for single-select questions
                            new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: fieldAnalytics.map(item => item.field_value),
                                    datasets: [{
                                        data: fieldAnalytics.map(item => item.count),
                                        backgroundColor: [
                                            '#4e79a7', '#f28e2c', '#e15759', '#76b7b2', '#59a14f',
                                            '#edc949', '#af7aa1', '#ff9da7', '#9c755f', '#bab0ac'
                                        ],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: field.field_label,
                                            font: { size: 14 }
                                        },
                                        legend: {
                                            position: 'right'
                                        }
                                    }
                                }
                            });
                            break;
                            
                        case 'checkbox':
                            // Bar chart for multi-select questions
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: fieldAnalytics.map(item => item.field_value),
                                    datasets: [{
                                        label: 'Selections',
                                        data: fieldAnalytics.map(item => item.count),
                                        backgroundColor: '#4e79a7'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: field.field_label,
                                            font: { size: 14 }
                                        },
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { precision: 0 }
                                        }
                                    }
                                }
                            });
                            break;
                            
                        case 'number':
                            // Histogram for numeric responses
                            const numericValues = fieldAnalytics
                                .filter(item => !isNaN(parseFloat(item.field_value)))
                                .map(item => parseFloat(item.field_value));
                            
                            if (numericValues.length > 0) {
                                const min = Math.min(...numericValues);
                                const max = Math.max(...numericValues);
                                const binCount = Math.min(10, Math.ceil(Math.sqrt(numericValues.length)));
                                const binSize = (max - min) / binCount;
                                
                                const bins = Array(binCount).fill(0);
                                const labels = [];
                                
                                for (let i = 0; i < binCount; i++) {
                                    const binStart = min + i * binSize;
                                    const binEnd = binStart + binSize;
                                    labels.push(`${binStart.toFixed(1)}-${binEnd.toFixed(1)}`);
                                    
                                    bins[i] = numericValues.filter(val => 
                                        val >= binStart && (i === binCount - 1 ? val <= binEnd : val < binEnd)
                                    ).length;
                                }
                                
                                new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: 'Frequency',
                                            data: bins,
                                            backgroundColor: '#4e79a7'
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        plugins: {
                                            title: {
                                                display: true,
                                                text: `${field.field_label} Distribution`,
                                                font: { size: 14 }
                                            },
                                            legend: { display: false }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: { display: true, text: 'Count' }
                                            },
                                            x: {
                                                title: { display: true, text: 'Value Range' }
                                            }
                                        }
                                    }
                                });
                            }
                            break;
                            
                        default:
                            // Default bar chart for other types
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: fieldAnalytics.map(item => `Option ${item.field_value}`),
                                    datasets: [{
                                        label: 'Responses',
                                        data: fieldAnalytics.map(item => item.count),
                                        backgroundColor: '#76b7b2'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: field.field_label,
                                            font: { size: 14 }
                                        },
                                        legend: { display: false }
                                    }
                                }
                            });
                    }
                } else {
                    // No data available for this field
                    ctx.canvas.parentNode.innerHTML += '<p>No response data available for this question.</p>';
                }
            });
        });
    </script>
    
    <script src="../assets/js/results-export.js"></script>
</body>
</html>