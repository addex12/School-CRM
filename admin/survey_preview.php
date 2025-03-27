<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: surveys.php");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Get target roles
$target_roles = json_decode($survey['target_roles'], true);

$pageTitle = "Preview: " . htmlspecialchars($survey['title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .survey-info {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .preview-field {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }

        .field-meta {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #6c757d;
        }

        .rating-star {
            color: #ffd700;
            font-size: 1.5em;
            margin-right: 5px;
        }

        .required {
            color: #dc3545;
            margin-left: 5px;
        }

        .form-actions {
            margin-top: 30px;
            text-align: center;
        }

        .btn-edit {
            background-color: #17a2b8;
            color: white;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1 class="page-title"><?= htmlspecialchars($survey['title']) ?> Preview</h1>
            </header>

            <div class="content">
                <div class="survey-info">
                    <h2>Survey Information</h2>
                    <p><strong>Description:</strong> <?= htmlspecialchars($survey['description']) ?></p>
                    <p><strong>Target Audience:</strong>
                        <?php
                        $roleNames = [
                            'student' => 'Students',
                            'teacher' => 'Teachers',
                            'parent' => 'Parents'
                        ];
                        echo implode(', ', array_map(fn($role) => $roleNames[$role] ?? ucfirst($role), $target_roles));
                        ?>
                    </p>
                    <p><strong>Status:</strong>
                        <?php if (!$survey['is_active']): ?>
                            <span class="status-inactive">Inactive</span>
                        <?php elseif (strtotime($survey['starts_at']) > time()): ?>
                            <span class="status-upcoming">Upcoming</span>
                        <?php elseif (strtotime($survey['ends_at']) < time()): ?>
                            <span class="status-ended">Ended</span>
                        <?php else: ?>
                            <span class="status-active">Active</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Availability:</strong>
                        <?= date('M j, Y g:i A', strtotime($survey['starts_at'])) ?> -
                        <?= date('M j, Y g:i A', strtotime($survey['ends_at'])) ?>
                    </p>
                </div>

                <div class="preview-container">
                    <?php foreach ($fields as $field): ?>
                        <div class="preview-field">
                            <h3><?= htmlspecialchars($field['field_label']) ?>
                                <?php if ($field['is_required']): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </h3>
                            
                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" class="form-control" disabled placeholder="Text input">

                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea class="form-control" rows="3" disabled placeholder="Textarea input"></textarea>

                            <?php elseif (in_array($field['field_type'], ['radio', 'checkbox', 'dropdown'])): ?>
                                <div class="options">
                                    <?php
                                    $options = json_decode($field['field_options'], true);
                                    foreach ($options as $option):
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="<?= $field['field_type'] === 'radio' ? 'radio' : 'checkbox' ?>" 
                                                   disabled>
                                            <label class="form-check-label">
                                                <?= htmlspecialchars($option) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($field['field_type'] === 'rating'): ?>
                                <div class="rating-container">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="rating-star">★</span>
                                    <?php endfor; ?>
                                </div>

                            <?php elseif ($field['field_type'] === 'file'): ?>
                                <input type="file" class="form-control" disabled>

                            <?php endif; ?>

                            <div class="field-meta">
                                <p><strong>Field Type:</strong> <?= ucfirst(str_replace('_', ' ', $field['field_type'])) ?></p>
                                <p><strong>Technical Name:</strong> <code><?= htmlspecialchars($field['field_name']) ?></code></p>
                                <p><strong>Required:</strong> <?= $field['is_required'] ? 'Yes' : 'No' ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="form-actions">
                        <a href="survey_builder.php?survey_id=<?= $survey['id'] ?>" class="btn btn-edit">
                            <i class="fas fa-edit"></i> Edit Survey
                        </a>
                        <a href="results.php?survey_id=<?= $survey['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>