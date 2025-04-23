<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
ob_start();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/db.php';

$pageTitle = "Survey Statistics";

// Fetch all surveys for selection
$surveyStmt = $pdo->prepare("SELECT id, title, starts_at, ends_at, is_anonymous FROM surveys ORDER BY created_at DESC");
$surveyStmt->execute();
$allSurveys = $surveyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected survey ID
$selected_survey_id = filter_input(INPUT_GET, 'survey_id', FILTER_VALIDATE_INT);

$survey = null;
$fields = [];
$analytics = [];
$total_responses = 0;

if ($selected_survey_id) {
    // Fetch survey details
    $surveyStmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
    $surveyStmt->execute([$selected_survey_id]);
    $survey = $surveyStmt->fetch(PDO::FETCH_ASSOC);

    if ($survey) {
        // Get total responses count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses WHERE survey_id = ?");
        $countStmt->execute([$selected_survey_id]);
        $total_responses = $countStmt->fetchColumn();

        // Fetch survey fields
        $fieldsStmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
        $fieldsStmt->execute([$selected_survey_id]);
        $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare analytics data for charts
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
                $analytics[$field['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
}

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
    <title><?= esc($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .content { max-width: 1100px; margin: 0 auto; }
        .erpnext-btn, .btn, .btn-primary, .btn-secondary {
            display: inline-block;
            padding: 10px 22px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #215967;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            margin-right: 8px;
            text-decoration: none;
        }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #eaeaea; color: #666; }
        .btn-secondary:hover { background: #e2efda; color: #215967; }
        .admin-header h1 {
            color: #215967;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }
        .filter-section {
            margin-bottom: 2rem;
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
        }
        .filter-form label {
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.5rem;
            display: block;
        }
        .filter-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
        }
        .survey-summary {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        .survey-summary p {
            margin-bottom: 8px;
            font-size: 1rem;
        }
        .survey-summary strong {
            color: #4f46e5;
            font-weight: 600;
        }
        .alert {
            background: #e2efda;
            color: #215967;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #b7e4c7;
            font-size: 1.05rem;
        }
        .alert-info {
            background: #f1f5f9;
            color: #2563eb;
            border: 1px solid #c7d2fe;
        }
        .chart-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        .chart-title {
            margin-top: 0;
            color: #215967;
            font-size: 1.2rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
            margin: 15px 0;
        }
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 5px;
            display: inline-block;
        }
        @media (max-width: 900px) {
            .admin-main, .content { padding: 1rem; }
            .chart-wrapper { height: 220px; }
        }
        @media (max-width: 600px) {
            .admin-main, .content { padding: 4px; }
            .chart-title { font-size: 1rem; }
            .survey-summary, .chart-container, .filter-section { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-chart-pie"></i> <?= esc($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <label for="survey_id">Select Survey</label>
                        <select name="survey_id" id="survey_id" onchange="this.form.submit()">
                            <option value="">-- Select a Survey --</option>
                            <?php foreach ($allSurveys as $surveyOption): ?>
                                <option value="<?= htmlspecialchars($surveyOption['id']) ?>" <?= ($selected_survey_id == $surveyOption['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($surveyOption['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if ($selected_survey_id && $survey): ?>
                    <div class="survey-summary">
                        <p><strong>Title:</strong> <?= htmlspecialchars($survey['title']) ?></p>
                        <p><strong>Start Date:</strong> <?= date('M j, Y', strtotime($survey['starts_at'])) ?></p>
                        <p><strong>End Date:</strong> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></p>
                        <p><strong>Anonymous:</strong> <?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></p>
                        <p><strong>Total Responses:</strong> <?= number_format($total_responses) ?></p>
                    </div>

                    <?php if ($total_responses > 0): ?>
                        <?php foreach ($fields as $field): ?>
                            <div class="chart-container">
                                <h3 class="chart-title"><?= htmlspecialchars($field['field_label']) ?></h3>
                                <div class="chart-wrapper">
                                    <canvas id="fieldChart-<?= $field['id'] ?>"></canvas>
                                </div>
                                <div class="chart-legend" id="legend-<?= $field['id'] ?>"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No responses found for this survey.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please select a survey to view statistics.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Color palette for charts
        const colorPalette = [
            '#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe',
            '#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5',
            '#f59e0b', '#fbbf24', '#fcd34d', '#fde68a', '#fef3c7',
            '#ef4444', '#f87171', '#fca5a5', '#fecaca', '#fee2e2'
        ];

        const chartData = <?= $chart_json ?>;

        document.addEventListener('DOMContentLoaded', function() {
            if (chartData.total_responses > 0) {
                chartData.fields.forEach((field, index) => {
                    const fieldAnalytics = chartData.analytics[field.id] || [];
                    const ctx = document.getElementById(`fieldChart-${field.id}`).getContext('2d');
                    
                    if (fieldAnalytics.length > 0) {
                        const chartType = getChartType(field.field_type);
                        createChart(ctx, field, fieldAnalytics, chartType, index);
                    } else {
                        ctx.canvas.parentNode.innerHTML += `
                            <div class="alert alert-info" style="margin-top: 15px;">
                                <i class="fas fa-info-circle"></i> No response data available for this question.
                            </div>
                        `;
                    }
                });
            }
        });

        function getChartType(fieldType) {
            switch(fieldType) {
                case 'radio':
                case 'select':
                case 'rating':
                    return 'doughnut';
                case 'checkbox':
                    return 'bar';
                case 'number':
                    return 'histogram';
                default:
                    return 'bar';
            }
        }

        function createChart(ctx, field, data, chartType, index) {
            switch(chartType) {
                case 'doughnut':
                    const doughnutChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.map(item => item.field_value),
                            datasets: [{
                                data: data.map(item => item.count),
                                backgroundColor: colorPalette,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((context.raw / total) * 100);
                                            return `${context.label}: ${context.raw} (${percentage}%)`;
                                        }
                                    }
                                },
                                datalabels: {
                                    display: false
                                }
                            },
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            }
                        }
                    });
                    
                    // Create custom legend
                    generateLegend(field.id, doughnutChart);
                    break;

                case 'bar':
                    const barChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(item => item.field_value),
                            datasets: [{
                                label: 'Responses',
                                data: data.map(item => item.count),
                                backgroundColor: colorPalette[index % colorPalette.length],
                                borderWidth: 0,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `${context.dataset.label}: ${context.raw}`;
                                        }
                                    }
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
                        }
                    });
                    break;

                case 'histogram':
                    const numericValues = data
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
                                    backgroundColor: colorPalette[10],
                                    borderWidth: 0,
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `Count: ${context.raw}`;
                                            }
                                        }
                                    }
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
                    break;

                default:
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(item => `Option ${item.field_value}`),
                            datasets: [{
                                label: 'Responses',
                                data: data.map(item => item.count),
                                backgroundColor: colorPalette[index % colorPalette.length],
                                borderWidth: 0,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
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
        }

        function generateLegend(chartId, chart) {
            const legendContainer = document.getElementById(`legend-${chartId}`);
            if (!legendContainer) return;
            
            const ul = document.createElement('div');
            ul.className = 'chart-legend';
            
            chart.data.labels.forEach((label, i) => {
                const li = document.createElement('div');
                li.className = 'legend-item';
                
                const colorSpan = document.createElement('span');
                colorSpan.className = 'legend-color';
                colorSpan.style.backgroundColor = chart.data.datasets[0].backgroundColor[i];
                
                const textSpan = document.createElement('span');
                textSpan.textContent = label;
                
                li.appendChild(colorSpan);
                li.appendChild(textSpan);
                ul.appendChild(li);
            });
            
            legendContainer.appendChild(ul);
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>