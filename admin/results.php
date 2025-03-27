<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
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
    handleDatabaseError($e);
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
            $stmt = $pdo->prepare("SELECT r.*, u.username, u.role 
                                 FROM survey_responses r
                                 JOIN users u ON r.user_id = u.id
                                 WHERE r.survey_id = ?
                                 ORDER BY r.submitted_at DESC");
            $stmt->execute([$survey_id]);
            $respondents = $stmt->fetchAll();

            // Calculate completion rate
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT u.id) 
                                 FROM users u
                                 WHERE JSON_CONTAINS(:target_roles, JSON_QUOTE(u.role))");
            $stmt->execute(['target_roles' => $survey['target_roles']]);
            $total_users = $stmt->fetchColumn();
            $completion_rate = $total_users > 0 ? (count($respondents) / $total_users) * 100 : 0;

            // Prepare response data
            foreach ($questions as $question) {
                $stats = [
                    'question' => $question,
                    'responses' => [],
                    'summary' => null
                ];

                if (in_array($question['field_type'], ['rating', 'number'])) {
                    $stmt = $pdo->prepare("SELECT AVG(CAST(rd.field_value AS DECIMAL(10,2))) as avg_value, 
                                         COUNT(rd.id) as response_count
                                         FROM response_data rd
                                         WHERE rd.field_id = ?");
                    $stmt->execute([$question['id']]);
                    $stats['summary'] = $stmt->fetch();
                }

                $response_data[$question['id']] = $stats;
            }

            // Get individual responses
            foreach ($respondents as $respondent) {
                $stmt = $pdo->prepare("SELECT rd.*, sf.field_label, sf.field_type
                                     FROM response_data rd
                                     JOIN survey_fields sf ON rd.field_id = sf.id
                                     WHERE rd.response_id = ?");
                $stmt->execute([$respondent['id']]);
                $responses = $stmt->fetchAll();

                foreach ($responses as $response) {
                    $response_data[$response['field_id']]['responses'][] = $response;
                }
            }
        }
    } catch(PDOException $e) {
        handleDatabaseError($e);
    }
}

// Start HTML output with header
include 'includes/admin_sidebar.php';
?>

<div class="admin-container">
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <h1><i class="fas fa-chart-bar"></i> Survey Results</h1>
        </div>

        <div class="results-container">
            <div class="survey-selector card">
                <form method="GET">
                    <div class="form-group">
                        <label>Select Survey:</label>
                        <select name="survey_id" class="form-control" onchange="this.form.submit()">
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
            <div class="results-header card">
                <h2><?= htmlspecialchars($survey['title']) ?></h2>
                <p class="text-muted"><?= htmlspecialchars($survey['description']) ?></p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Responses</h3>
                        <p><?= number_format(count($respondents)) ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Completion Rate</h3>
                        <p><?= number_format($completion_rate, 1) ?>%</p>
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
                <div class="respondents-list card">
                    <h3>Respondents</h3>
                    <div class="list-container">
                        <?php foreach ($respondents as $respondent): ?>
                        <div class="respondent-item" data-response-id="<?= $respondent['id'] ?>">
                            <div class="respondent-meta">
                                <strong><?= htmlspecialchars($respondent['username']) ?></strong>
                                <span class="badge role-badge"><?= ucfirst($respondent['role']) ?></span>
                            </div>
                            <small><?= date('M j, Y g:i a', strtotime($respondent['submitted_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="results-details card" id="results-details">
                    <?php foreach ($response_data as $data): ?>
                    <div class="question-result">
                        <h4><?= htmlspecialchars($data['question']['field_label']) ?></h4>
                        <p class="text-muted">
                            <?= ucfirst(str_replace('_', ' ', $data['question']['field_type'])) ?> |
                            <?= $data['question']['is_required'] ? 'Required' : 'Optional' ?>
                        </p>

                        <?php if ($data['question']['field_type'] === 'rating' && $data['summary']): ?>
                        <div class="chart-container">
                            <canvas id="chart-rating-<?= $data['question']['id'] ?>"></canvas>
                        </div>
                        <?php endif; ?>

                        <?php if ($data['question']['field_type'] === 'checkbox'): ?>
                        <div class="chart-container">
                            <canvas id="chart-checkbox-<?= $data['question']['id'] ?>"></canvas>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <div class="export-options">
                        <h4>Export Results</h4>
                        <div class="btn-group">
                            <a href="export.php?type=csv&survey_id=<?= $survey_id ?>" class="btn btn-export">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="export.php?type=excel&survey_id=<?= $survey_id ?>" class="btn btn-export">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                            <a href="export.php?type=pdf&survey_id=<?= $survey_id ?>" class="btn btn-export">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state card">
                <div class="empty-content">
                    <i class="fas fa-poll-h fa-4x"></i>
                    <h3>No Survey Selected</h3>
                    <p>Please select a survey from the dropdown to view results</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    <?php foreach ($response_data as $data): ?>
    <?php if ($data['question']['field_type'] === 'rating' && $data['summary']): ?>
    new Chart(document.getElementById('chart-rating-<?= $data['question']['id'] ?>'), {
        type: 'bar',
        data: {
            labels: ['★', '★★', '★★★', '★★★★', '★★★★★'],
            datasets: [{
                label: 'Rating Distribution',
                data: [<?= implode(',', array_map(fn($i) => count(array_filter($data['responses'], fn($r) => $r['field_value'] == $i)), range(1,5))) ?>],
                backgroundColor: '#36a2eb'
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
    <?php endif; ?>

    <?php if ($data['question']['field_type'] === 'checkbox'): ?>
    const options<?= $data['question']['id'] ?> = <?= json_encode(json_decode($data['question']['field_options'])) ?>;
    const counts<?= $data['question']['id'] ?> = {};
    <?php foreach ($data['responses'] as $response): ?>
    (<?= $response['field_value'] ?>).split(', ').forEach(v => counts<?= $data['question']['id'] ?>[v] = (counts<?= $data['question']['id'] ?>[v] || 0) + 1);
    <?php endforeach; ?>
    
    new Chart(document.getElementById('chart-checkbox-<?= $data['question']['id'] ?>'), {
        type: 'pie',
        data: {
            labels: options<?= $data['question']['id'] ?>,
            datasets: [{
                data: options<?= $data['question']['id'] ?>.map(o => counts<?= $data['question']['id'] ?>[o] || 0),
                backgroundColor: ['#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0', '#9966ff']
            }]
        }
    });
    <?php endif; ?>
    <?php endforeach; ?>

    // Respondent selection
    document.querySelectorAll('.respondent-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.respondent-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            // Implement AJAX loading of individual response here
        });
    });
});
</script>