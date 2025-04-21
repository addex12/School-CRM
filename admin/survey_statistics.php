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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
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
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>

            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <label for="survey_id">Select Survey</label>
                    <select name="survey_id" id="survey_id" class="form-control" onchange="this.form.submit()">
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
                <div class="survey-summary mb-4">
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
                            <canvas id="fieldChart-<?= $field['id'] ?>" height="100"></canvas>
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

    <script>
        const chartData = <?= $chart_json ?>;

        document.addEventListener('DOMContentLoaded', function() {
            if (chartData.total_responses > 0) {
                chartData.fields.forEach(field => {
                    const fieldAnalytics = chartData.analytics[field.id] || [];
                    const ctx = document.getElementById(`fieldChart-${field.id}`).getContext('2d');

                    if (fieldAnalytics.length > 0) {
                        switch(field.field_type) {
                            case 'radio':
                            case 'select':
                            case 'rating':
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
                        ctx.canvas.parentNode.innerHTML += '<div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> No response data available for this question.</div>';
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
