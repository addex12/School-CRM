<?php
// Start session at the very top for session reliability
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$response_id = $_GET['id'] ?? 0;
$pageTitle = 'Response Details';
if (!$response_id) {
    $_SESSION['error'] = "Response ID is required.";
    header("Location: results.php");
    exit();
}

// Get response info with survey details
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.email, ro.role_name, s.title AS survey_title,
           s.is_anonymous, s.description AS survey_description
    FROM survey_responses r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN roles ro ON u.role_id = ro.id
    JOIN surveys s ON r.survey_id = s.id
    WHERE r.id = ?
");
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
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-response-container {
            max-width: 1000px;
            margin: 24px auto;
            padding: 18px 12px 28px 12px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
        }
        .adugna-response-header {
            background: #f8f9fa;
            padding: 18px 18px 18px 18px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .adugna-response-title {
            margin-top: 0;
            color: #1976d2;
            font-size: 1.35em;
            font-weight: 700;
        }
        .adugna-response-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .adugna-meta-item {
            background: #fff;
            padding: 10px 12px;
            border-radius: 7px;
            box-shadow: 0 1px 3px rgba(25,118,210,0.04);
        }
        .adugna-meta-label {
            font-weight: 600;
            color: #7f8c8d;
            font-size: 0.93em;
        }
        .adugna-meta-value {
            margin-top: 5px;
            font-size: 1.08em;
            color: #222d32;
            word-break: break-word;
        }
        .adugna-response-items {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }
        .adugna-response-item {
            background: #fff;
            padding: 18px 18px 14px 18px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(25,118,210,0.04);
            border-left: 4px solid #3498db;
            transition: box-shadow 0.15s;
        }
        .adugna-response-item.unanswered {
            border-left-color: #e74c3c;
            opacity: 0.7;
        }
        .adugna-response-question {
            font-weight: 700;
            margin-bottom: 10px;
            color: #1976d2;
            font-size: 1.05em;
        }
        .adugna-response-answer {
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 10px;
            word-break: break-word;
        }
        .adugna-rating-stars {
            color: #f39c12;
            font-size: 1.3em;
            letter-spacing: 2px;
        }
        .adugna-file-preview {
            max-width: 100%;
            max-height: 300px;
            display: block;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .adugna-actions {
            margin-top: 30px;
            text-align: center;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 7px 18px;
            font-size: 1em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
            margin-right: 0.7em;
        }
        .adugna-btn-print {
            background: #34495e;
            color: #fff;
        }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-badge {
            display: inline-block;
            padding: 0.25em 0.7em;
            font-size: 0.85em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            background: #e74c3c;
            color: #fff;
            margin-left: 0.7em;
        }
        .adugna-text-muted {
            color: #6c757d !important;
        }
        @media (max-width: 900px) {
            .adugna-response-container { padding: 10px 2vw 18px 2vw; }
            .adugna-response-header { padding: 12px 8px; }
        }
        @media (max-width: 600px) {
            .adugna-response-container { padding: 4px 1vw 10px 1vw; }
            .adugna-response-header { padding: 7px 2px; }
            .adugna-response-title { font-size: 1.08em; }
            .adugna-response-meta { grid-template-columns: 1fr; gap: 8px; }
            .adugna-response-item { padding: 10px 4px 8px 8px; }
            .adugna-response-question { font-size: 1em; }
            .adugna-response-answer { font-size: 0.97em; }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="adugna-response-container">
        <div class="adugna-response-header">
            <h2 class="adugna-response-title"><?= htmlspecialchars($response['survey_title']) ?></h2>
            <p class="survey-description"><?= htmlspecialchars($response['survey_description']) ?></p>
            <div class="adugna-response-meta">
                <div class="adugna-meta-item">
                    <div class="adugna-meta-label">Respondent</div>
                    <div class="adugna-meta-value">
                        <?= $response['is_anonymous'] ? 'Anonymous' : htmlspecialchars($response['username'] ?? 'N/A') ?>
                    </div>
                </div>
                <div class="adugna-meta-item">
                    <div class="adugna-meta-label">Role</div>
                    <div class="adugna-meta-value"><?= htmlspecialchars($response['role_name'] ?? 'N/A') ?></div>
                </div>
                <?php if (!$response['is_anonymous'] && $response['email']): ?>
                <div class="adugna-meta-item">
                    <div class="adugna-meta-label">Email</div>
                    <div class="adugna-meta-value"><?= htmlspecialchars($response['email']) ?></div>
                </div>
                <?php endif; ?>
                <div class="adugna-meta-item">
                    <div class="adugna-meta-label">Submitted At</div>
                    <div class="adugna-meta-value">
                        <?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="adugna-response-items">
            <?php foreach ($fields as $field): ?>
                <div class="adugna-response-item <?= !isset($answered_data[$field['id']]) ? 'unanswered' : '' ?>">
                    <div class="adugna-response-question">
                        <?= htmlspecialchars($field['field_label']) ?>
                        <?php if (!isset($answered_data[$field['id']])): ?>
                            <span class="adugna-badge">Not answered</span>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($answered_data[$field['id']])): ?>
                        <?php $answer = $answered_data[$field['id']]; ?>
                        <div class="adugna-response-answer">
                            <?php switch ($field['field_type']):
                                case 'rating': ?>
                                    <div class="adugna-rating-stars">
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
                                        <?php 
                                        $values = json_decode($answer['field_value'], true);
                                        if (!is_array($values)) {
                                            if ($answer['field_value'] === '' || $answer['field_value'] === null) {
                                                $values = [];
                                            } else {
                                                $values = [$answer['field_value']];
                                            }
                                        }
                                        foreach ($values as $value): ?>
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
                                                <img src="<?= $filepath ?>" class="adugna-file-preview" alt="Uploaded file">
                                            <?php else: ?>
                                                <a href="<?= $filepath ?>" target="_blank" class="adugna-btn">
                                                    <i class="bi bi-download"></i> Download File
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p style="color:#e74c3c;">File not found</p>
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
                        <div class="adugna-response-answer">
                            <p class="adugna-text-muted">Question was not answered</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="adugna-actions">
            <a href="results.php?survey_id=<?= $response['survey_id'] ?>" class="adugna-btn">
                <i class="bi bi-arrow-left"></i> Back to Results
            </a>
            <button onclick="window.print()" class="adugna-btn adugna-btn-print">
                <i class="bi bi-printer"></i> Print Response
            </button>
        </div>
    </div>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>