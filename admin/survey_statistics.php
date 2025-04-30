<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
 */
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

        // Additional: Prepare data for overall survey response trends (e.g., responses over time)
        $response_trend = [];
        if ($selected_survey_id) {
            $trendStmt = $pdo->prepare("
                SELECT DATE(submitted_at) as response_date, COUNT(*) as count
                FROM survey_responses
                WHERE survey_id = ?
                GROUP BY response_date
                ORDER BY response_date ASC
            ");
            $trendStmt->execute([$selected_survey_id]);
            $response_trend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Additional: Prepare data for pie chart of anonymous vs non-anonymous responses (if possible)
        $anon_stats = [];
        if ($selected_survey_id) {
            $anonStmt = $pdo->prepare("
                SELECT is_anonymous, COUNT(*) as count
                FROM surveys
                WHERE id = ?
                GROUP BY is_anonymous
            ");
            $anonStmt->execute([$selected_survey_id]);
            $anon_stats = $anonStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$chart_data = [
    'survey' => $survey,
    'fields' => $fields,
    'analytics' => $analytics,
    'total_responses' => $total_responses,
    'response_trend' => $response_trend,
    'anon_stats' => $anon_stats
];
$chart_json = json_encode($chart_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Statistics - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting and clarity */
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 6px 32px 0 rgba(80, 112, 255, 0.08), 0 1.5px 6px 0 rgba(80, 112, 255, 0.03);
            border: none;
            padding: 1.5rem 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
            max-width: 900px;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.5em;
            padding: 0.28rem 0.85rem;
            font-size: 0.97em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 0.97em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .adugna-btn.adugna-btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        .adugna-btn.adugna-btn-secondary:hover {
            background: #e5e7eb;
            color: #22223b;
        }
        .adugna-btn.adugna-btn-sm {
            padding: 0.18rem 0.6rem;
            font-size: 0.91em;
        }
        .adugna-chart-container {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px rgba(80,112,255,0.08), 0 1.5px 6px rgba(80,112,255,0.03);
            padding: 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            border: none;
            width: 100%;
            max-width: 900px;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        .adugna-chart-title {
            margin-top: 0;
            color: #4f46e5;
            font-size: 1.13rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .adugna-chart-wrapper {
            position: relative;
            height: 320px;
            margin: 15px 0;
        }
        .adugna-chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .adugna-legend-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        .adugna-legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 5px;
            display: inline-block;
        }
        .adugna-survey-summary {
            background: #f3f4f6;
            border-radius: 1.1rem;
            padding: 1.3rem 1.1rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
            border-left: 4px solid #4f46e5;
            max-width: 900px;
            width: 100%;
        }
        .adugna-survey-summary p {
            margin-bottom: 8px;
            font-size: 0.97rem;
        }
        .adugna-survey-summary strong {
            color: #4f46e5;
            font-weight: 600;
        }
        @media (max-width: 900px) {
            .adugna-card, .adugna-chart-container, .adugna-survey-summary { padding: 1rem 0.5rem; max-width: 100vw; }
            .adugna-chart-wrapper { height: 220px; }
        }
        @media (max-width: 600px) {
            .adugna-card, .adugna-chart-container, .adugna-survey-summary { padding: 0.5rem 0.1rem; }
            .adugna-chart-title { font-size: 1em; }
            .adugna-chart-wrapper { height: 160px; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
        /* Adugna Gizaw: Additional compact and responsive adugna- styles for new charts */
        .adugna-row-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5vw;
            justify-content: space-between;
        }
        .adugna-col-half {
            flex: 1 1 45%;
            min-width: 320px;
            max-width: 48%;
        }
        @media (max-width: 900px) {
            .adugna-row-flex { flex-direction: column; gap: 2vw; }
            .adugna-col-half { max-width: 100%; min-width: 0; }
        }
        .adugna-chart-summary {
            background: #f3f4f6;
            border-radius: 1.1rem;
            padding: 1rem 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
            border-left: 4px solid #4f46e5;
            font-size: 0.98em;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard adugna-admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main adugna-admin-main">
            <header class="admin-header">
                <h1 style="color:#4f46e5;font-weight:800;"><i class="fas fa-chart-pie"></i> Survey Statistics</h1>
            </header>
            <div class="content" style="width:100%;max-width:900px;margin:0 auto;">
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <label for="survey_id">Select Survey</label>
                        <select name="survey_id" id="survey_id" class="adugna-form-control" onchange="this.form.submit()">
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
                    <div class="adugna-survey-summary">
                        <p><strong>Title:</strong> <?= htmlspecialchars($survey['title']) ?></p>
                        <p><strong>Start Date:</strong> <?= date('M j, Y', strtotime($survey['starts_at'])) ?></p>
                        <p><strong>End Date:</strong> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></p>
                        <p><strong>Anonymous:</strong> <?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></p>
                        <p><strong>Total Responses:</strong> <?= number_format($total_responses) ?></p>
                    </div>
                    <?php if ($total_responses > 0): ?>
                        <!-- Adugna Gizaw: Show overall response trend and anonymous stats in a row -->
                        <div class="adugna-row-flex">
                            <div class="adugna-col-half">
                                <div class="adugna-chart-container">
                                    <h3 class="adugna-chart-title"><i class="fas fa-chart-line"></i> Responses Over Time</h3>
                                    <div class="adugna-chart-wrapper" style="height:220px;">
                                        <canvas id="adugna-trend-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="adugna-col-half">
                                <div class="adugna-chart-container">
                                    <h3 class="adugna-chart-title"><i class="fas fa-user-secret"></i> Anonymous vs Non-Anonymous</h3>
                                    <div class="adugna-chart-wrapper" style="height:220px;">
                                        <canvas id="adugna-anon-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Adugna Gizaw: Show per-question charts as before -->
                        <?php foreach ($fields as $field): ?>
                            <div class="adugna-chart-container">
                                <h3 class="adugna-chart-title"><i class="fas fa-chart-bar"></i> <?= htmlspecialchars($field['field_label']) ?></h3>
                                <div class="adugna-chart-wrapper">
                                    <canvas id="fieldChart-<?= $field['id'] ?>"></canvas>
                                </div>
                                <div class="adugna-chart-legend" id="legend-<?= $field['id'] ?>"></div>
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
        // Adugna Gizaw: Outstanding, interactive, responsive charts with adugna- theme

        const colorPalette = [
            '#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe',
            '#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5',
            '#f59e0b', '#fbbf24', '#fcd34d', '#fde68a', '#fef3c7',
            '#ef4444', '#f87171', '#fca5a5', '#fecaca', '#fee2e2'
        ];
        const chartData = <?= $chart_json ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Responses Over Time (Line Chart)
            if (chartData.response_trend && chartData.response_trend.length > 0) {
                const ctxTrend = document.getElementById('adugna-trend-chart').getContext('2d');
                new Chart(ctxTrend, {
                    type: 'line',
                    data: {
                        labels: chartData.response_trend.map(item => item.response_date),
                        datasets: [{
                            label: 'Responses',
                            data: chartData.response_trend.map(item => item.count),
                            fill: true,
                            backgroundColor: 'rgba(79,70,229,0.08)',
                            borderColor: '#4f46e5',
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: '#6366f1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
                        }
                    }
                });
            }

            // Anonymous vs Non-Anonymous (Pie Chart)
            if (chartData.anon_stats && chartData.anon_stats.length > 0) {
                const ctxAnon = document.getElementById('adugna-anon-chart').getContext('2d');
                const labels = chartData.anon_stats.map(item => item.is_anonymous == 1 ? 'Anonymous' : 'Not Anonymous');
                const data = chartData.anon_stats.map(item => item.count);
                new Chart(ctxAnon, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [colorPalette[0], colorPalette[10]],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((context.raw / total) * 100);
                                        return `${context.label}: ${context.raw} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Per-question charts (existing logic)
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
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((context.raw / total) * 100);
                                            return `${context.label}: ${context.raw} (${percentage}%)`;
                                        }
                                    }
                                }
                            },
                            animation: {
                                animateScale: true,
                                animateRotate: true,
                                duration: 1200,
                                easing: 'easeOutElastic'
                            }
                        }
                    });
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
                                borderRadius: 8
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
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
                                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                                },
                                y: { grid: { display: false } }
                            },
                            animation: {
                                duration: 1200,
                                easing: 'easeOutElastic'
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
                                    borderRadius: 8
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
                                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                                    },
                                    x: { grid: { display: false } }
                                },
                                animation: {
                                    duration: 1200,
                                    easing: 'easeOutElastic'
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
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                                x: { grid: { display: false } }
                            },
                            animation: {
                                duration: 1200,
                                easing: 'easeOutElastic'
                            }
                        }
                    });
            }
        }

        function generateLegend(chartId, chart) {
            const legendContainer = document.getElementById(`legend-${chartId}`);
            if (!legendContainer) return;
            const ul = document.createElement('div');
            ul.className = 'adugna-chart-legend';
            chart.data.labels.forEach((label, i) => {
                const li = document.createElement('div');
                li.className = 'adugna-legend-item';
                const colorSpan = document.createElement('span');
                colorSpan.className = 'adugna-legend-color';
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
</body>
</html>
<?php
ob_end_flush();
?>