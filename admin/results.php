<?php
require_once '../includes/config.php'; // Include config to initialize $pdo
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['survey_id'] ?? 0;

// Get survey info if specific survey is selected
$survey = null;
if ($survey_id) {
    $stmt = $pdo->prepare(query: "SELECT * FROM surveys WHERE id = ?");
    $stmt->execute(params: [$survey_id]);
    $survey = $stmt->fetch();
}

// Get all surveys for dropdown
$surveys = $pdo->query("SELECT id, title FROM surveys ORDER BY created_at DESC")->fetchAll();

// Get responses data if survey is selected
$response_data = [];
$questions = [];
$summary_stats = [];
$respondents = [];

if ($survey) {
    // Get questions for this survey
    $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll();
    
    // Get all responses for this survey
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.role
        FROM survey_responses r
        JOIN users u ON r.user_id = u.id
        WHERE r.survey_id = ?
        ORDER BY r.submitted_at DESC
    ");
    $stmt->execute([$survey_id]);
    $respondents = $stmt->fetchAll();
    
    // Get response data for summary
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
    
    // Calculate completion rate
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT u.id) as total_users
        FROM users u
        WHERE JSON_CONTAINS(?, JSON_QUOTE(u.role))
    ");
    $stmt->execute([$survey['target_roles']]);
    $total_users = $stmt->fetchColumn();
    $completion_rate = $total_users > 0 ? (count($respondents) / $total_users) * 100 : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Results - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .survey-selector {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .results-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .results-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }
        .respondents-list {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        .respondent-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .respondent-item:hover {
            background-color: #f5f5f5;
        }
        .respondent-item.active {
            background-color: #e3f2fd;
        }
        .results-details {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .question-result {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .chart-container {
            margin: 20px 0;
        }
        .response-item {
            margin-bottom: 15px;
        }
        .export-options {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <>
        <body>
    <div class="container">
        <header>
            <h1>Survey Results - Admin Panel</h1>
            <?php include 'includes/admin_sidebar.php'; ?>
        </header>                
        <div class="content">
            <div class="survey-selector">
                <form method="GET">
                    <div class="form-group">
                        <label for="survey_id">Select Survey:</label>
                        <select id="survey_id" name="survey_id" onchange="this.form.submit()">
                            <option value="">-- Select a Survey --</option>
                            <?php foreach ($surveys as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $survey_id == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if ($survey): ?>
                <div class="results-header">
                    <h2><?php echo htmlspecialchars(string: $survey['title']); ?></h2>
                    <p><?php echo htmlspecialchars($survey['description'] ?? ''); ?></p>
                    <div class="stats">
                        <p><strong>Total Responses:</strong> <?php echo count(value: $respondents); ?></p>
                        <p><strong>Completion Rate:</strong> <?php echo round(num: $completion_rate, precision: 2); ?>%</p>
                        <p><strong>Survey Period:</strong> 
                            <?php echo date(format: 'M j, Y', timestamp: strtotime(datetime: $survey['starts_at'])); ?> to 
                            <?php echo date(format: 'M j, Y', timestamp: strtotime($survey['ends_at'])); ?>
                        </p>
                    </div>
                </div>
                
                <div class="results-grid">
                    <div class="respondents-list">
                        <h3>Respondents</h3>
                        <?php foreach ($respondents as $respondent): ?>
                            <div class="respondent-item" onclick="showRespondent(<?php echo $respondent['id']; ?>)">
                                <p><strong><?php echo htmlspecialchars($respondent['username']); ?></strong></p>
                                <p><?php echo ucfirst(string: $respondent['role']); ?></p>
                                <p><?php echo date(format: 'M j, Y g:i a', timestamp: strtotime(datetime: $respondent['submitted_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="results-details" id="results-details">
                        <h2>Survey Results Summary</h2>
                        
                        <?php foreach ($response_data as $data): ?>
                            <div class="question-result">
                                <h3><?php echo htmlspecialchars($data['question']['field_label'] ?? ''); ?></h3>
                                <p><strong>Type:</strong> <?php echo ucfirst(string: str_replace(search: '_', replace: ' ', subject: $data['question']['field_type'])); ?></p>
                                
                                <?php if ($data['question']['field_type'] === 'rating' && $data['summary']): ?>
                                    <div class="chart-container">
                                        <canvas id="chart-<?php echo $data['question']['id']; ?>"></canvas>
                                    </div>
                                    <p>Average Rating: <?php echo number_format(num: $data['summary']['avg_value'], decimals: 2); ?>/5</p>
                                    
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const ctx = document.getElementById('chart-<?php echo $data['question']['id']; ?>').getContext('2d');
                                            const chart = new Chart(ctx, {
                                                type: 'bar',
                                                data: {
                                                    labels: ['1', '2', '3', '4', '5'],
                                                    datasets: [{
                                                        label: 'Rating Distribution',
                                                        data: [
                                                            <?php 
                                                            $counts = [0, 0, 0, 0, 0];
                                                            foreach ($data['responses'] as $response) {
                                                                $rating = intval(value: $response['field_value']);
                                                                if ($rating >= 1 && $rating <= 5) {
                                                                    $counts[$rating - 1]++;
                                                                }
                                                            }
                                                            echo implode(separator: ', ', array: $counts);
                                                            ?>
                                                        ],
                                                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                                        borderColor: 'rgba(54, 162, 235, 1)',
                                                        borderWidth: 1
                                                    }]
                                                },
                                                options: {
                                                    scales: {
                                                        y: {
                                                            beginAtZero: true,
                                                            title: {
                                                                display: true,
                                                                text: 'Number of Responses'
                                                            }
                                                        },
                                                        x: {
                                                            title: {
                                                                display: true,
                                                                text: 'Rating'
                                                            }
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    </script>
                                
                                <?php elseif ($data['question']['field_type'] === 'number' && $data['summary']): ?>
                                    <p>Average Value: <?php echo isset($data['summary']) ? number_format(num: $data['summary']['avg_value'], decimals: 2) : 'N/A'; ?></p>
                                    <p>Total Responses: <?php echo isset($data['summary']) ? $data['summary']['response_count'] : 0; ?></p>
                                
                                <?php elseif (in_array(needle: $data['question']['field_type'], haystack: ['radio', 'select'])): ?>
                                    <div class="chart-container">
                                        <canvas id="chart-<?php echo $data['question']['id']; ?>"></canvas>
                                    </div>
                                    
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const ctx = document.getElementById('chart-<?php echo $data['question']['id']; ?>').getContext('2d');
                                            const options = <?php echo $data['question']['field_options'] ?: '[]'; ?>;
                                            const counts = {};
                                            
                                            // Initialize counts
                                            options.forEach(option => {
                                                counts[option] = 0;
                                            });
                                            
                                            // Count responses
                                            <?php 
                                            foreach ($data['responses'] as $response) {
                                                echo "counts['{$response['field_value']}']++;";
                                            }
                                            ?>
                                            
                                            const chart = new Chart(ctx, {
                                                type: 'pie',
                                                data: {
                                                    labels: options,
                                                    datasets: [{
                                                        data: options.map(option => counts[option]),
                                                        backgroundColor: [
                                                            'rgba(255, 99, 132, 0.5)',
                                                            'rgba(54, 162, 235, 0.5)',
                                                            'rgba(255, 206, 86, 0.5)',
                                                            'rgba(75, 192, 192, 0.5)',
                                                            'rgba(153, 102, 255, 0.5)'
                                                        ],
                                                        borderColor: [
                                                            'rgba(255, 99, 132, 1)',
                                                            'rgba(54, 162, 235, 1)',
                                                            'rgba(255, 206, 86, 1)',
                                                            'rgba(75, 192, 192, 1)',
                                                            'rgba(153, 102, 255, 1)'
                                                        ],
                                                        borderWidth: 1
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    plugins: {
                                                        legend: {
                                                            position: 'right',
                                                        },
                                                        title: {
                                                            display: true,
                                                            text: 'Response Distribution'
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    </script>
                                
                                <?php elseif ($data['question']['field_type'] === 'checkbox'): ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Option</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $options = json_decode(json: $data['question']['field_options'], associative: true);
                                            $total = count(value: $data['responses']);
                                            
                                            if (is_array(value: $options)) {
                                                foreach ($options as $option) {
                                                    $count = 0;
                                                    foreach ($data['responses'] as $response) {
                                                        $values = explode(separator: ', ', string: $response['field_value']);
                                                        if (in_array(needle: $option, haystack: $values)) {
                                                            $count++;
                                                        }
                                                    }
                                                    $percentage = $total > 0 ? round(num: ($count / $total) * 100, precision: 2) : 0;
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(string: $option); ?></td>
                                                        <td><?php echo $count; ?></td>
                                                        <td><?php echo $percentage; ?>%</td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                
                                <?php else: ?>
                                    <h4>Sample Responses:</h4>
                                    <div class="response-samples">
                                        <?php 
                                        $sample = array_slice(array: $data['responses'], offset: 0, length: 5);
                                        foreach ($sample as $response): ?>
                                            <div class="response-item">
                                                <p><?php echo nl2br(htmlspecialchars($response['field_value'] ?? '')); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="export-options">
                            <h3>Export Results</h3>
                            <a href="export.php?type=csv&survey_id=<?php echo $survey_id; ?>" class="btn">Export as CSV</a>
                            <a href="export.php?type=excel&survey_id=<?php echo $survey_id; ?>" class="btn">Export as Excel</a>
                            <a href="export.php?type=pdf&survey_id=<?php echo $survey_id; ?>" class="btn">Export as PDF</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-survey-selected">
                    <p>Please select a survey from the dropdown above to view results.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showRespondent(responseId) {
            // Highlight selected respondent
            document.querySelectorAll('.respondent-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('onclick').includes(responseId)) {
                    item.classList.add('active');
                }
            });
            
            // In a real implementation, you would fetch and display the respondent's details
            // For now, we'll just show a message
            document.getElementById('results-details').innerHTML = `
                <h2>Respondent Details</h2>
                <p>Loading response data for ID: ${responseId}</p>
                <p>In a full implementation, this would show the individual's complete response.</p>
                <a href="response_view.php?id=${responseId}" class="btn">View Full Response</a>
                <a href="results.php?survey_id=<?php echo $survey_id; ?>" class="btn">Back to Summary</a>
            `;
        }
    </script>
</body>
</html>