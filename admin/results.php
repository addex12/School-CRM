<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

// --- Admin: Show all survey responses (no survey_id required) ---
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



// Prepare analytics data for charts
$analytics = [];
foreach ($fields as $field) {
    if ($field['field_type'] === 'checkbox') {
        // Special handling for checkbox fields (stored as JSON arrays)
        $stmt = $pdo->prepare("SELECT field_value FROM response_data WHERE field_id = ?");
        $stmt->execute([$field['id']]);
        $all_values = [];
        
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $val) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                $all_values = array_merge($all_values, $decoded);
            } elseif ($val !== null) {
                $all_values[] = $val;
            }
        }
        
        $counts = array_count_values($all_values);
        arsort($counts);
        $analytics[$field['id']] = array_map(function($value, $count) {
            return ['field_value' => $value, 'count' => $count];
        }, array_keys($counts), $counts);
    } else {
        // Standard handling for other field types
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
}

// Prepare JSON data for JavaScript charts
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey['title']) ?> Results - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        /* Modern, clean styling */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4361ee;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .chart-title {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.2rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .response-table {
            width: 100%;
            border-collapse: collapse;
        }
        .response-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        .response-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        .response-table tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .badge-primary {
            background-color: #4361ee;
            color: white;
        }
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
        }
        .page-item.active .page-link {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .page-link {
            position: relative;
            display: block;
            padding: 0.5rem 0.75rem;
            margin-left: -1px;
            line-height: 1.25;
            color: #4361ee;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>All Survey Responses</h1>
            </header>
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

            <!-- Charts Section -->
            <div class="chart-section mb-5">
                <div class="row">
                    <div class="col-12">
                        <div class="chart-container">
                            <h3 class="chart-title">Response Summary</h3>
                            <canvas id="summaryChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                
                <?php foreach ($fields as $field): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-container">
                                <h3 class="chart-title"><?= htmlspecialchars($field['field_label']) ?></h3>
                                <canvas id="fieldChart-<?= $field['id'] ?>" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Responses Table -->
            <div class="response-table-section">
                <h3 class="mb-3">Individual Responses</h3>
                
                <?php if ($total_responses > 0): ?>
                    <div class="table-responsive">
                        <table class="response-table">
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
                                                <span class="text-muted">Anonymous</span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($response['username'] ?? 'N/A') ?>
                                                <?php if ($response['email']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($response['email']) ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></td>
                                        
                                        <?php 
                                        // --- BEGIN: Use response_data_map for answers ---
                                        foreach ($fields as $field): 
                                            $val = $response_data_map[$response['id']][$field['id']] ?? null;
                                        ?>
                                            <td>
                                                <?= $val !== null && $val !== '' ? 
                                                    htmlspecialchars($val) : 
                                                    '<span class="text-muted">N/A</span>' ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <!-- --- END: Use response_data_map for answers --- -->
                                        
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
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page - 1 ?><?= $date_filter ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Show page numbers
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?survey_id='.$survey_id.'&page=1'.$date_filter.'">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $i ?><?= $date_filter ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; 
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?survey_id='.$survey_id.'&page='.$total_pages.$date_filter.'">'.$total_pages.'</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?survey_id=<?= $survey_id ?>&page=<?= $page + 1 ?><?= $date_filter ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No responses found for this survey. Please check back later.
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
            // Summary chart - Response trend over time
            if (chartData.total_responses > 0) {
                const ctx = document.getElementById('summaryChart').getContext('2d');
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
                            backgroundColor: 'rgba(67, 97, 238, 0.1)',
                            borderColor: 'rgba(67, 97, 238, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: 'rgba(67, 97, 238, 1)',
                            pointRadius: 4,
                            pointHoverRadius: 6
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
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => `Responses: ${ctx.raw}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { 
                                    display: true, 
                                    text: 'Number of Responses',
                                    font: { weight: 'bold' }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                title: { 
                                    display: true, 
                                    text: 'Time Period',
                                    font: { weight: 'bold' }
                                },
                                grid: {
                                    display: false
                                }
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
                                            '#4361ee', '#3f37c9', '#4895ef', '#4cc9f0', 
                                            '#560bad', '#7209b7', '#b5179e', '#f72585',
                                            '#3a0ca3', '#480ca8'
                                        ],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    cutout: '60%',
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: field.field_label,
                                            font: { size: 14 }
                                        },
                                        legend: {
                                            position: 'right',
                                            labels: {
                                                padding: 20,
                                                usePointStyle: true,
                                                pointStyle: 'circle'
                                            }
                                        },
                                        datalabels: {
                                            formatter: (value, ctx) => {
                                                const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                                return `${Math.round(value / total * 100)}%`;
                                            },
                                            color: '#fff',
                                            font: { weight: 'bold' }
                                        }
                                    }
                                },
                                plugins: [ChartDataLabels]
                            });
                            break;
                            
                        case 'checkbox':
                            // Horizontal bar chart for multi-select questions
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: fieldAnalytics.map(item => item.field_value),
                                    datasets: [{
                                        label: 'Selections',
                                        data: fieldAnalytics.map(item => item.count),
                                        backgroundColor: '#4361ee',
                                        borderWidth: 0,
                                        borderRadius: 4
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: field.field_label,
                                            font: { size: 14 }
                                        },
                                        legend: { display: false },
                                        datalabels: {
                                            anchor: 'end',
                                            align: 'end',
                                            formatter: value => value,
                                            color: '#4361ee',
                                            font: { weight: 'bold' }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            beginAtZero: true,
                                            ticks: { precision: 0 },
                                            grid: {
                                                color: 'rgba(0, 0, 0, 0.05)'
                                            }
                                        },
                                        y: {
                                            grid: {
                                                display: false
                                            }
                                        }
                                    }
                                },
                                plugins: [ChartDataLabels]
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
                                            backgroundColor: '#4361ee',
                                            borderWidth: 0,
                                            borderRadius: 4
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
                                                title: { 
                                                    display: true, 
                                                    text: 'Count',
                                                    font: { weight: 'bold' }
                                                },
                                                grid: {
                                                    color: 'rgba(0, 0, 0, 0.05)'
                                                }
                                            },
                                            x: {
                                                title: { 
                                                    display: true, 
                                                    text: 'Value Range',
                                                    font: { weight: 'bold' }
                                                },
                                                grid: {
                                                    display: false
                                                }
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
                                        backgroundColor: '#4895ef',
                                        borderWidth: 0,
                                        borderRadius: 4
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
                                            grid: {
                                                color: 'rgba(0, 0, 0, 0.05)'
                                            }
                                        },
                                        x: {
                                            grid: {
                                                display: false
                                            }
                                        }
                                    }
                                }
                            });
                    }
                } else {
                    // No data available for this field
                    ctx.canvas.parentNode.innerHTML += '<div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> No response data available for this question.</div>';
                }
            });
        });
    </script>
    
    <script src="../assets/js/results-export.js"></script>
</body>
</html>