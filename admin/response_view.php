<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// No output before redirects!
$response_id = $_GET['id'] ?? null;
if (!$response_id) {
    $_SESSION['error'] = "Response ID is required.";
    header("Location: results.php");
    exit();
}

// Fetch response details
$stmt = $pdo->prepare("SELECT sr.*, s.title AS survey_title, s.description AS survey_description, s.is_anonymous, u.username, u.email, r.role_name
    FROM survey_responses sr
    JOIN surveys s ON sr.survey_id = s.id
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE sr.id = ?");
$stmt->execute([$response_id]);
$response = $stmt->fetch();

if (!$response) {
    $_SESSION['error'] = "Response not found.";
    header("Location: results.php");
    exit();
}

// Get all fields for this survey to show unanswered questions
$stmt = $pdo->prepare("
    SELECT id, field_label, field_type, field_options
    FROM survey_fields
    WHERE survey_id = ?
    ORDER BY display_order
");
$stmt->execute([$response['survey_id']]);
$fields = $stmt->fetchAll();

// Get response data for answered questions
$stmt = $pdo->prepare("
    SELECT d.*, f.field_label, f.field_type, f.field_options
    FROM response_data d
    JOIN survey_fields f ON d.field_id = f.id
    WHERE d.response_id = ?
");
$stmt->execute([$response_id]);
$response_data = $stmt->fetchAll();

// Create a map of field_id to response data for easy lookup
$answered_data = [];
foreach ($response_data as $data) {
    if ($data['field_type'] === 'checkbox') {
        $decoded = json_decode($data['field_value'], true);
        $data['field_value'] = is_array($decoded) ? $decoded : [$data['field_value']];
    }
    $answered_data[$data['field_id']] = $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Response Details - <?= htmlspecialchars($response['survey_title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .response-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .response-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .response-title {
            margin-top: 0;
            color: #2c3e50;
        }
        .response-meta {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .meta-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .meta-label {
            font-weight: bold;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .meta-value {
            margin-top: 5px;
            font-size: 1.1em;
        }
        .response-items {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .response-item {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        .response-item.unanswered {
            border-left-color: #e74c3c;
            opacity: 0.7;
        }
        .response-question {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .response-answer {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 10px;
        }
        .rating-stars {
            color: #f39c12;
            font-size: 1.5em;
            letter-spacing: 2px;
        }
        .file-preview {
            max-width: 100%;
            max-height: 300px;
            display: block;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn-print {
            background: #34495e;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .bg-danger {
            background-color: #dc3545 !important;
            color: white;
        }
        .text-muted {
            color: #6c757d !important;
        }
    </style>
</head>
<body>
    <?php
    if (!empty($_SESSION['error'])) {
        echo '<div style="color:red; font-weight:bold; text-align:center; margin:20px 0;">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    ?>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="response-container">
        <div class="response-header">
            <h2 class="response-title"><?= htmlspecialchars($response['survey_title']) ?></h2>
            <p class="survey-description"><?= htmlspecialchars($response['survey_description']) ?></p>
            
            <div class="response-meta">
                <div class="meta-item">
                    <div class="meta-label">Respondent</div>
                    <div class="meta-value">
                        <?= $response['is_anonymous'] ? 'Anonymous' : htmlspecialchars($response['username'] ?? 'N/A') ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">Role</div>
                    <div class="meta-value"><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></div>
                </div>
                
                <?php if (!$response['is_anonymous'] && $response['email']): ?>
                <div class="meta-item">
                    <div class="meta-label">Email</div>
                    <div class="meta-value"><?= htmlspecialchars($response['email']) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="meta-item">
                    <div class="meta-label">Submitted At</div>
                    <div class="meta-value">
                        <?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="response-items">
            <?php foreach ($fields as $field): ?>
                <div class="response-item <?= !isset($answered_data[$field['id']]) ? 'unanswered' : '' ?>">
                    <div class="response-question">
                        <?= htmlspecialchars($field['field_label']) ?>
                        <?php if (!isset($answered_data[$field['id']])): ?>
                            <span class="badge bg-danger">Not answered</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($answered_data[$field['id']])): ?>
                        <?php $answer = $answered_data[$field['id']]; ?>
                        
                        <div class="response-answer">
                            <?php switch ($field['field_type']):
                                case 'rating': ?>
                                    <div class="rating-stars">
                                        <?php 
                                        $rating = intval($answer['field_value']);
                                        echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                                        ?>
                                        <span class="rating-value">(<?= $rating ?>/5)</span>
                                    </div>
                                    <?php break;
                                
                                case 'radio':
                                case 'select': ?>
                                    <p><?= htmlspecialchars($answer['field_value']) ?></p>
                                    <?php break;
                                
                                case 'checkbox': ?>
                                    <ul>
                                        <?php foreach ($answer['field_value'] as $value): ?>
                                            <li><?= htmlspecialchars($value) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php break;
                                
                                case 'file': ?>
                                    <?php if ($answer['field_value']): ?>
                                        <?php 
                                        $filepath = "../uploads/survey_{$response['survey_id']}/{$answer['field_value']}";
                                        if (file_exists($filepath)): 
                                            $fileinfo = pathinfo($filepath);
                                            if (in_array(strtolower($fileinfo['extension']), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                <img src="<?= $filepath ?>" class="file-preview" alt="Uploaded file">
                                            <?php else: ?>
                                                <a href="<?= $filepath ?>" target="_blank" class="btn btn-primary">
                                                    <i class="bi bi-download"></i> Download File
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-danger">File not found</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p>No file uploaded</p>
                                    <?php endif; ?>
                                    <?php break;
                                
                                default: ?>
                                    <p><?= nl2br(htmlspecialchars($answer['field_value'])) ?></p>
                            <?php endswitch; ?>
                        </div>
                    <?php else: ?>
                        <div class="response-answer">
                            <p class="text-muted">Question was not answered</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="actions">
            <a href="results.php?survey_id=<?= $response['survey_id'] ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to Results
            </a>
            <button onclick="window.print()" class="btn btn-print">
                <i class="bi bi-printer"></i> Print Response
            </button>
        </div>
    </div>

    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>