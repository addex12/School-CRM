<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$response_id = $_GET['id'] ?? null;
if (!$response_id) {
    $_SESSION['error'] = "Response ID is required.";
    header("Location: results.php");
    exit();
}

// Fetch response details
$stmt = $pdo->prepare("
    SELECT sr.*, s.title AS survey_title, s.description AS survey_description, 
           u.username, u.email, r.role_name 
    FROM survey_responses sr
    JOIN surveys s ON sr.survey_id = s.id
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE sr.id = ?
");
$stmt->execute([$response_id]);
$response = $stmt->fetch();

if (!$response) {
    $_SESSION['error'] = "Response not found.";
    header("Location: results.php");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("
    SELECT id, field_label, field_type 
    FROM survey_fields 
    WHERE survey_id = ?
    ORDER BY display_order
");
$stmt->execute([$response['survey_id']]);
$fields = $stmt->fetchAll();

// Decode answers
$answers = json_decode($response['answers'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Response: <?= htmlspecialchars($response['survey_title']) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .response-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .response-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .question-item {
            border-left: 4px solid #3b82f6;
            margin-bottom: 15px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
        }
        .question-item.unanswered {
            border-left-color: #ef4444;
            opacity: 0.7;
        }
        .file-preview {
            max-width: 300px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="admin-main">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Response Details</h1>
                <a href="results.php?survey_id=<?= $response['survey_id'] ?>" 
                   class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Results
                </a>
            </div>

            <!-- Response Header -->
            <div class="response-card">
                <div class="response-header">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="fw-bold text-muted">Survey Title</div>
                            <div><?= htmlspecialchars($response['survey_title']) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="fw-bold text-muted">Respondent</div>
                            <div>
                                <?= $response['is_anonymous'] ? 'Anonymous' : 
                                    htmlspecialchars($response['username'] ?? 'N/A') ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="fw-bold text-muted">Submitted At</div>
                            <div>
                                <?= date('M j, Y H:i', strtotime($response['submitted_at'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions List -->
                <div class="p-4">
                    <?php foreach ($fields as $field): 
                        $answer = $answers[$field['id']] ?? null;
                    ?>
                    <div class="question-item <?= $answer ? '' : 'unanswered' ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="fw-bold"><?= htmlspecialchars($field['field_label']) ?></div>
                            <?php if (!$answer): ?>
                            <span class="badge bg-danger">Not Answered</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($answer): ?>
                            <?php if ($field['field_type'] === 'file'): ?>
                                <?php if (file_exists("../uploads/$answer")): ?>
                                <img src="../uploads/<?= $answer ?>" 
                                     class="file-preview" 
                                     alt="Uploaded file">
                                <?php else: ?>
                                <div class="text-danger">File not found</div>
                                <?php endif; ?>
                            <?php elseif ($field['field_type'] === 'checkbox'): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ((array)$answer as $item): ?>
                                    <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div><?= htmlspecialchars(is_array($answer) ? implode(', ', $answer) : $answer) ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-muted">No answer provided</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>