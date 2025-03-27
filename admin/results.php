<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$survey_id = isset($_GET['survey_id']) ? (int)$_GET['survey_id'] : 0;
$survey = null;
$surveys = [];
$response_data = [];
$questions = [];
$respondents = [];
$completion_rate = 0;

// Fetch all available surveys
try {
    $surveys = $pdo->query("SELECT id, title FROM surveys ORDER BY created_at DESC")->fetchAll();
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Process selected survey
if ($survey_id) {
    try {
        // Get survey details
        $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->execute([$survey_id]);
        $survey = $stmt->fetch();

        if ($survey) {
            // Get survey questions/fields
            $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
            $stmt->execute([$survey_id]);
            $questions = $stmt->fetchAll();

            // Get respondents
            $stmt = $pdo->prepare("
                SELECT r.*, u.username, u.role 
                FROM survey_responses r
                JOIN users u ON r.user_id = u.id
                WHERE r.survey_id = ?
                ORDER BY r.submitted_at DESC
            ");
            $stmt->execute([$survey_id]);
            $respondents = $stmt->fetchAll();

            // Calculate completion rate
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT u.id) 
                FROM users u
                WHERE JSON_CONTAINS(:target_roles, JSON_QUOTE(u.role))
            ");
            $stmt->execute([':target_roles' => $survey['target_roles']]);
            $total_users = $stmt->fetchColumn();
            $completion_rate = $total_users > 0 ? (count($respondents) / $total_users * 100) : 0;

            // Prepare response data
            foreach ($questions as $question) {
                $stats = [
                    'question' => $question,
                    'responses' => [],
                    'summary' => null
                ];

                if (in_array($question['field_type'], ['rating', 'number'])) {
                    $stmt = $pdo->prepare("
                        SELECT AVG(CAST(rd.field_value AS DECIMAL(10,2))) as avg_value, 
                               COUNT(rd.id) as response_count
                        FROM response_data rd
                        WHERE rd.field_id = ?
                    ");
                    $stmt->execute([$question['id']]);
                    $stats['summary'] = $stmt->fetch();
                }

                $response_data[$question['id']] = $stats;
            }

            // Get individual responses
            foreach ($respondents as $respondent) {
                $stmt = $pdo->prepare("
                    SELECT rd.*, sf.field_label, sf.field_type
                    FROM response_data rd
                    JOIN survey_fields sf ON rd.field_id = sf.id
                    WHERE rd.response_id = ?
                ");
                $stmt->execute([$respondent['id']]);
                $responses = $stmt->fetchAll();

                foreach ($responses as $response) {
                    $response_data[$response['field_id']]['responses'][] = $response;
                }
            }
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Results - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .content-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        header.admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: #2c3e50;
            color: #fff;
        }

        header.admin-header .page-title {
            margin: 0;
            font-size: 1.5rem;
        }

        header.admin-header .header-actions .btn {
            background: #3498db;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        header.admin-header .header-actions .btn:hover {
            background: #2980b9;
        }

        footer {
            text-align: center;
            padding: 1rem;
            background-color: #2c3e50;
            color: #fff;
            margin-top: auto;
        }

        .results-container {
            margin-top: 1.5rem;
        }

        .survey-selector {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .results-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 0.5rem;
            font-size: 1rem;
            color: #666;
        }

        .stat-card p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .results-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .respondents-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 1rem;
        }

        .respondent-item {
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .respondent-item:hover {
            transform: translateX(5px);
            background: #e9ecef;
        }

        .question-result {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .chart-container {
            margin: 1.5rem 0;
            position: relative;
            height: 300px;
        }

        .export-options {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #eee;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-active { background: #e3fcec; color: #2a9d8f; }
        .status-ended { background: #ffe3e3; color: #e63946; }
        .status-upcoming { background: #fff3cd; color: #d4a106; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 class="page-title"><?= htmlspecialchars($survey['title']) ?> Results</h1>
                <div class="header-actions">
                    <a href="surveys.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Surveys
                    </a>
                </div>
            </header>
        <main class="main-content">
            
            <div class="content-header">
                <h1>Survey Results</h1>
            </div>

            <div class="results-container">
                <div class="survey-selector">
                    <form method="GET">
                        <div class="form-group">
                            <label>Select Survey:</label>
                            <select name="survey_id" onchange="this.form.submit()">
                                <option value="">-- Select a Survey --</option>
                                <?php foreach ($surveys as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $survey_id == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <?php if ($survey): ?>
                <div class="results-header">
                    <h2><?= htmlspecialchars($survey['title']) ?></h2>
                    <p><?= htmlspecialchars($survey['description']) ?></p>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Total Responses</h3>
                            <p><?= count($respondents) ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Completion Rate</h3>
                            <p><?= round($completion_rate, 1) ?>%</p>
                        </div>
                        <div class="stat-card">
                            <h3>Survey Period</h3>
                            <p>
                                <?= date('M j, Y', strtotime($survey['starts_at'])) ?> - 
                                <?= date('M j, Y', strtotime($survey['ends_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="results-grid">
                    <div class="respondents-list">
                        <h3>Respondents</h3>
                        <?php foreach ($respondents as $respondent): ?>
                        <div class="respondent-item" onclick="showRespondent(<?= $respondent['id'] ?>)">
                            <div class="respondent-meta">
                                <strong><?= htmlspecialchars($respondent['username']) ?></strong>
                                <span class="status-badge">
                                    <?= ucfirst($respondent['role']) ?>
                                </span>
                            </div>
                            <small><?= date('M j, Y g:i a', strtotime($respondent['submitted_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="results-details" id="results-details">
                        <?php foreach ($response_data as $data): ?>
                        <div class="question-result">
                            <h3><?= htmlspecialchars($data['question']['field_label']) ?></h3>
                            <p class="text-muted">
                                <?= ucfirst(str_replace('_', ' ', $data['question']['field_type'])) ?> |
                                <?= $data['question']['is_required'] ? 'Required' : 'Optional' ?>
                            </p>

                            <?php if ($data['question']['field_type'] === 'rating' && $data['summary']): ?>
                            <div class="chart-container">
                                <canvas id="chart-<?= $data['question']['id'] ?>"></canvas>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                new Chart(document.getElementById('chart-<?= $data['question']['id'] ?>'), {
                                    type: 'bar',
                                    data: {
                                        labels: ['1', '2', '3', '4', '5'],
                                        datasets: [{
                                            label: 'Rating Distribution',
                                            data: [
                                                <?php
                                                $counts = array_fill(0, 5, 0);
                                                foreach ($data['responses'] as $response) {
                                                    $rating = (int)$response['field_value'];
                                                    if ($rating >= 1 && $rating <= 5) {
                                                        $counts[$rating - 1]++;
                                                    }
                                                }
                                                echo implode(', ', $counts);
                                                ?>
                                            ],
                                            backgroundColor: '#36a2eb80',
                                            borderColor: '#36a2eb',
                                            borderWidth: 1
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                                        }
                                    }
                                });
                            });
                            </script>
                            <?php endif; ?>

                            <?php if ($data['question']['field_type'] === 'checkbox'): ?>
                            <div class="chart-container">
                                <canvas id="chart-<?= $data['question']['id'] ?>"></canvas>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const options = <?= json_encode(json_decode($data['question']['field_options'])) ?>;
                                const counts = {};
                                
                                options.forEach(option => counts[option] = 0);
                                <?php foreach ($data['responses'] as $response): ?>
                                    const values = '<?= $response['field_value'] ?>'.split(', ');
                                    values.forEach(v => counts[v] = (counts[v] || 0) + 1);
                                <?php endforeach; ?>

                                new Chart(document.getElementById('chart-<?= $data['question']['id'] ?>'), {
                                    type: 'doughnut',
                                    data: {
                                        labels: options,
                                        datasets: [{
                                            data: options.map(o => counts[o]),
                                            backgroundColor: [
                                                '#ff6384', '#36a2eb', '#ffcd56', 
                                                '#4bc0c0', '#9966ff', '#ff9f40'
                                            ]
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { position: 'right' }
                                        }
                                    }
                                });
                            });
                            </script>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <div class="export-options">
                            <h4>Export Results</h4>
                            <div class="btn-group">
                                <a href="export.php?type=csv&survey_id=<?= $survey_id ?>" class="btn">
                                    <i class="fas fa-file-csv"></i> CSV
                                </a>
                                <a href="export.php?type=excel&survey_id=<?= $survey_id ?>" class="btn">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                                <a href="export.php?type=pdf&survey_id=<?= $survey_id ?>" class="btn">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-survey">
                    <div class="empty-state">
                        <i class="fas fa-poll-h fa-3x"></i>
                        <h3>No Survey Selected</h3>
                        <p>Please select a survey from the dropdown to view results</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> School CRM. All rights reserved.</p>
        </footer>
    </div>

    <script>
    function showRespondent(responseId) {
        document.querySelectorAll('.respondent-item').forEach(item => {
            item.classList.toggle('active', item.getAttribute('onclick').includes(responseId));
        });
        
        // In real implementation, fetch response details via AJAX
        document.getElementById('results-details').innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                Loading response details...
            </div>
        `;
    }
    </script>
</body>
</html>