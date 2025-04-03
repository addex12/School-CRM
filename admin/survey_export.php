<?php
/**
 * Survey Export Tool
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Export Survey Data";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $surveyId = $_POST['survey_id'];
    $format = $_POST['export_format'];
    $includeQuestions = isset($_POST['include_questions']);
    $includeResponses = isset($_POST['include_responses']);
    
    // Validate inputs
    if (empty($surveyId) || !is_numeric($surveyId)) {
        $_SESSION['error'] = "Invalid survey selection";
        header("Location: survey_export.php");
        exit();
    }
    
    
    // Fetch survey data
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, sc.name as category_name, ss.label as status_label, u.username as creator_name
            FROM surveys s
            LEFT JOIN survey_categories sc ON s.category_id = sc.id
            LEFT JOIN survey_statuses ss ON s.status_id = ss.id
            LEFT JOIN users u ON s.created_by = u.id
            WHERE s.id = ?
        ");
        if (!$stmt || !$stmt->execute([$surveyId])) {
            throw new Exception("Failed to fetch survey data.");
        }
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$survey) {
            throw new Exception("Survey not found");
        }
        
        // Prepare export data
        $exportData = [
            'survey' => [
                'id' => $survey['id'],
                'title' => $survey['title'],
                'description' => $survey['description'],
                'category' => $survey['category_name'],
                'status' => $survey['status_label'],
                'creator' => $survey['creator_name'],
                'start_date' => $survey['starts_at'],
                'end_date' => $survey['ends_at'],
                'is_anonymous' => (bool)$survey['is_anonymous'],
                'is_active' => (bool)$survey['is_active']
            ]
        ];
        
        // Include questions if requested
        if ($includeQuestions) {
            $stmt = $pdo->prepare("
                SELECT * FROM survey_fields 
                WHERE survey_id = ?
                ORDER BY display_order ASC
            ");
            if (!$stmt || !$stmt->execute([$surveyId])) {
                throw new Exception("Failed to fetch survey questions.");
            }
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $exportData['questions'] = $questions;
        }
        
        // Include responses if requested
        if ($includeResponses) {
            $stmt = $pdo->prepare("
                SELECT sr.*, u.username as respondent_name, 
                       DATE_FORMAT(sr.submitted_at, '%Y-%m-%d %H:%i:%s') as submitted_at_formatted
                FROM survey_responses sr
                LEFT JOIN users u ON sr.user_id = u.id
                WHERE sr.survey_id = ?
                ORDER BY sr.submitted_at DESC
            ");
            if (!$stmt || !$stmt->execute([$surveyId])) {
                throw new Exception("Failed to fetch survey responses.");
            }
            $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get response data for each response
            foreach ($responses as &$response) {
                $stmt = $pdo->prepare("
                    SELECT rd.*, sf.field_label, sf.field_type
                    FROM response_data rd
                    JOIN survey_fields sf ON rd.field_id = sf.id
                    WHERE rd.response_id = ?
                ");
                if (!$stmt || !$stmt->execute([$response['id']])) {
                    throw new Exception("Failed to fetch response data.");
                }
                $responseData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response['answers'] = $responseData;
            }
            
            $exportData['responses'] = $responses;
        }
        
        // Generate export file
        switch ($format) {
            case 'csv':
                exportAsCSV($exportData, $survey['title']);
                break;
                
            case 'json':
                exportAsJSON($exportData, $survey['title']);
                break;
                
            case 'excel':
                exportAsExcel($exportData, $survey['title']);
                break;
                
            default:
                throw new Exception("Invalid export format");
        }
        
    } catch (Exception $e) {
        error_log("Export error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to generate export: " . $e->getMessage();
        header("Location: survey_export.php");
        exit();
    }
}

// Get all surveys for dropdown
$surveys = $pdo->query("
    SELECT s.id, s.title, sc.name as category_name
    FROM surveys s
    LEFT JOIN survey_categories sc ON s.category_id = sc.id
    ORDER BY s.title ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/export.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Export survey data in multiple formats</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge"><?= countUnreadNotifications($pdo, $_SESSION['user_id']) ?></span>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications dropdown content -->
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-file-export"></i> Export Survey Data</h2>
                    </div>
                    
                    <form method="post" class="export-form">
                        <div class="form-group">
                            <label for="survey_id">Select Survey:</label>
                            <select name="survey_id" id="survey_id" required>
                                <option value="">-- Select a Survey --</option>
                                <?php foreach ($surveys as $survey): ?>
                                    <option value="<?= $survey['id'] ?>">
                                        <?= htmlspecialchars($survey['title']) ?> (<?= htmlspecialchars($survey['category_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Export Format:</label>
                            <div class="format-options">
                                <label class="format-option">
                                    <input type="radio" name="export_format" value="csv" checked>
                                    <div class="format-card">
                                        <i class="fas fa-file-csv"></i>
                                        <span>CSV</span>
                                    </div>
                                </label>
                                
                                <label class="format-option">
                                    <input type="radio" name="export_format" value="excel">
                                    <div class="format-card">
                                        <i class="fas fa-file-excel"></i>
                                        <span>Excel</span>
                                    </div>
                                </label>
                                
                                <label class="format-option">
                                    <input type="radio" name="export_format" value="json">
                                    <div class="format-card">
                                        <i class="fas fa-file-code"></i>
                                        <span>JSON</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Include in Export:</label>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="include_questions" checked>
                                    <span>Survey Questions</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="include_responses" checked>
                                    <span>Survey Responses</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="export" class="btn btn-export">
                                <i class="fas fa-download"></i> Generate Export
                            </button>
                        </div>
                    </form>
                    
                    <div class="export-instructions">
                        <h3><i class="fas fa-info-circle"></i> Export Instructions</h3>
                        <ul>
                            <li><strong>CSV:</strong> Comma-separated values, best for spreadsheet applications</li>
                            <li><strong>Excel:</strong> Microsoft Excel format (.xlsx)</li>
                            <li><strong>JSON:</strong> Structured data format, best for developers</li>
                        </ul>
                        <p>For large surveys with many responses, CSV/Excel exports may take several minutes to generate.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/export.js"></script>
</body>
</html>