<?php
/**
 * Survey View Page
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Survey Details";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get survey ID
$surveyId = $_GET['id'] ?? null;
if (!$surveyId) {
    $_SESSION['error'] = "No survey specified.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey data
try {
    // Main survey info
    $stmt = $pdo->prepare("SELECT s.*, sc.name as category_name, ss.label as status_label, u.username as creator 
                          FROM surveys s 
                          LEFT JOIN survey_categories sc ON s.category_id = sc.id 
                          LEFT JOIN survey_statuses ss ON s.status_id = ss.id 
                          LEFT JOIN users u ON s.created_by = u.id 
                          WHERE s.id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$survey) {
        $_SESSION['error'] = "Survey not found.";
        header("Location: surveys.php");
        exit();
    }
    
    // Survey fields
    $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
    $stmt->execute([$surveyId]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Survey roles
    $stmt = $pdo->prepare("SELECT r.role_name FROM survey_roles sr 
                          JOIN roles r ON sr.role_id = r.id 
                          WHERE sr.survey_id = ?");
    $stmt->execute([$surveyId]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Response count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses WHERE survey_id = ?");
    $stmt->execute([$surveyId]);
    $responseCount = $stmt->fetchColumn();
    
    // Recent responses (for the table)
    $stmt = $pdo->prepare("SELECT sr.*, u.username 
                          FROM survey_responses sr 
                          LEFT JOIN users u ON sr.user_id = u.id 
                          WHERE sr.survey_id = ? 
                          ORDER BY sr.submitted_at DESC 
                          LIMIT 5");
    $stmt->execute([$surveyId]);
    $recentResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Field response data (for charts)
    $fieldData = [];
    foreach ($fields as $field) {
        if (in_array($field['field_type'], ['radio', 'checkbox', 'select', 'rating'])) {
            $stmt = $pdo->prepare("SELECT rd.field_value, COUNT(*) as count 
                                  FROM response_data rd 
                                  JOIN survey_responses sr ON rd.response_id = sr.id 
                                  WHERE rd.field_id = ? AND sr.survey_id = ? 
                                  GROUP BY rd.field_value");
            $stmt->execute([$field['id'], $surveyId]);
            $fieldData[$field['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
} catch (Exception $e) {
    error_log("Survey view error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load survey data.";
    header("Location: surveys.php");
    exit();
}

// Format dates for display
$survey['starts_at'] = formatDate($survey['starts_at']);
$survey['ends_at'] = formatDate($survey['ends_at']);
$survey['created_at'] = formatDate($survey['created_at']);
$survey['updated_at'] = formatDate($survey['updated_at']);

// Determine survey status
$now = new DateTime();
$startDate = new DateTime($survey['starts_at']);
$endDate = new DateTime($survey['ends_at']);
$statusClass = '';

if ($survey['is_active'] == 0) {
    $statusText = 'Inactive';
    $statusClass = 'inactive';
} elseif ($now < $startDate) {
    $statusText = 'Scheduled';
    $statusClass = 'scheduled';
} elseif ($now > $endDate) {
    $statusText = 'Completed';
    $statusClass = 'completed';
} else {
    $statusText = 'Active';
    $statusClass = 'active';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/survey_view.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($survey['title']) ?></h1>
                    <p class="welcome-message">Survey Details</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications content would be here -->
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <!-- Survey Summary Section -->
                <div class="survey-summary">
                    <div class="summary-card">
                        <div class="summary-header">
                            <h2><i class="fas fa-info-circle"></i> Survey Information</h2>
                            <div class="survey-status <?= $statusClass ?>">
                                <?= $statusText ?>
                            </div>
                        </div>
                        <div class="summary-content">
                            <div class="summary-row">
                                <span class="summary-label">Description:</span>
                                <span class="summary-value"><?= htmlspecialchars($survey['description'] ?: 'No description') ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Category:</span>
                                <span class="summary-value"><?= htmlspecialchars($survey['category_name'] ?: 'Uncategorized') ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Status:</span>
                                <span class="summary-value"><?= htmlspecialchars($survey['status_label']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Created By:</span>
                                <span class="summary-value"><?= htmlspecialchars($survey['creator']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Created At:</span>
                                <span class="summary-value"><?= $survey['created_at'] ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Last Updated:</span>
                                <span class="summary-value"><?= $survey['updated_at'] ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Time Period:</span>
                                <span class="summary-value"><?= $survey['starts_at'] ?> to <?= $survey['ends_at'] ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Responses:</span>
                                <span class="summary-value"><?= $responseCount ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Access:</span>
                                <span class="summary-value"><?= implode(', ', $roles) ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Anonymous:</span>
                                <span class="summary-value"><?= $survey['is_anonymous'] ? 'Yes' : 'No' ?></span>
                            </div>
                        </div>
                        <div class="summary-actions">
                            <a href="survey_builder.php?id=<?= $surveyId ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Survey
                            </a>
                            <a href="survey_responses.php?id=<?= $surveyId ?>" class="btn btn-secondary">
                                <i class="fas fa-list"></i> View All Responses
                            </a>
                            <a href="survey_export.php?id=<?= $surveyId ?>" class="btn btn-outline">
                                <i class="fas fa-download"></i> Export Data
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Survey Analytics Section -->
                <div class="survey-analytics">
                    <div class="analytics-card">
                        <div class="analytics-header">
                            <h2><i class="fas fa-chart-bar"></i> Response Analytics</h2>
                            <div class="response-count">
                                Total Responses: <?= $responseCount ?>
                            </div>
                        </div>
                        
                        <?php if ($responseCount > 0): ?>
                            <div class="analytics-content">
                                <!-- Response Trend Chart -->
                                <div class="chart-container">
                                    <h3>Response Trend</h3>
                                    <canvas id="responseTrendChart"></canvas>
                                </div>
                                
                                <!-- Field Charts -->
                                <div class="field-charts">
                                    <?php foreach ($fields as $field): ?>
                                        <?php if (isset($fieldData[$field['id']]) && !empty($fieldData[$field['id']])): ?>
                                            <div class="chart-container">
                                                <h3><?= htmlspecialchars($field['field_label']) ?></h3>
                                                <canvas id="fieldChart-<?= $field['id'] ?>"></canvas>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-pie"></i>
                                <p>No responses yet. Analytics will appear when responses are collected.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Survey Questions Section -->
                <div class="survey-questions">
                    <div class="questions-card">
                        <div class="questions-header">
                            <h2><i class="fas fa-list-ol"></i> Survey Questions</h2>
                            <div class="questions-count">
                                <?= count($fields) ?> Questions
                            </div>
                        </div>
                        <div class="questions-list">
                            <?php foreach ($fields as $field): ?>
                                <div class="question-item">
                                    <div class="question-header">
                                        <div class="question-type">
                                            <i class="fas <?= getFieldTypeIcon($field['field_type']) ?>"></i>
                                            <?= ucfirst($field['field_type']) ?>
                                        </div>
                                        <?php if ($field['is_required']): ?>
                                            <div class="question-required">Required</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="question-text">
                                        <?= htmlspecialchars($field['field_label']) ?>
                                    </div>
                                    <?php if (in_array($field['field_type'], ['radio', 'checkbox', 'select', 'rating'])): ?>
                                        <div class="question-options">
                                            <strong>Options:</strong>
                                            <?php 
                                            $options = $field['field_options'] ? json_decode($field['field_options'], true) : [];
                                            echo htmlspecialchars(implode(', ', $options));
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Responses Section -->
                <div class="recent-responses">
                    <div class="responses-card">
                        <div class="responses-header">
                            <h2><i class="fas fa-clipboard-list"></i> Recent Responses</h2>
                            <a href="survey_responses.php?id=<?= $surveyId ?>" class="view-all">View All</a>
                        </div>
                        
                        <?php if (!empty($recentResponses)): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Response ID</th>
                                            <th>User</th>
                                            <th>Submitted At</th>
                                            <th>IP Address</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentResponses as $response): ?>
                                            <tr>
                                                <td><?= $response['id'] ?></td>
                                                <td><?= $response['username'] ? htmlspecialchars($response['username']) : ($survey['is_anonymous'] ? 'Anonymous' : 'N/A') ?></td>
                                                <td><?= formatDate($response['submitted_at']) ?></td>
                                                <td><?= htmlspecialchars($response['ip_address']) ?></td>
                                                <td>
                                                    <a href="response_view.php?id=<?= $response['id'] ?>" class="btn btn-sm btn-view">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>No responses have been submitted yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Pass PHP data to JavaScript
        const surveyData = {
            id: <?= $surveyId ?>,
            responseCount: <?= $responseCount ?>,
            fields: <?= json_encode($fields) ?>,
            fieldData: <?= json_encode($fieldData) ?>,
            responses: <?= json_encode($recentResponses) ?>
        };
        
        // Mock response trend data (replace with actual data from your database)
        const responseTrendData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            data: [12, 19, 8, 15, 22, 17]
        };
    </script>
    
    <script src="../assets/js/survey_view.js"></script>
</body>
</html>