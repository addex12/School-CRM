<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
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
        /* Improved layout structure */
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 20px 30px 80px; /* Added bottom padding for footer */
            overflow-y: auto;
            background: #f8f9fa;
        }

        .preview-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .survey-info {
            margin-bottom: 30px;
            padding: 25px;
            background: #f1f4f7;
            border-radius: 8px;
            border-left: 4px solid #4361ee;
        }

        .preview-field {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            transition: transform 0.2s ease;
        }

        .preview-field:hover {
            transform: translateX(5px);
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
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
        }

        /* Status indicators */
        .status-active { color: #28a745; font-weight: 500; }
        .status-inactive { color: #6c757d; }
        .status-upcoming { color: #ffc107; }
        .status-ended { color: #dc3545; }

        /* Form elements */
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-top: 8px;
        }

        .options {
            margin-top: 15px;
        }

        .form-check {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1 class="page-title"><?= htmlspecialchars($survey['title']) ?> Preview</h1>
                <div class="header-actions">
                    <a href="surveys.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Surveys
                    </a>
                </div>
            </header>

            <div class="content">
                <div class="survey-info">
                    <div class="survey-meta">
                        <h2>Survey Details</h2>
                        <div class="meta-grid">
                            <div class="meta-item">
                                <label>Description:</label>
                                <p><?= htmlspecialchars($survey['description']) ?></p>
                            </div>
                            <div class="meta-item">
                                <label>Target Audience:</label>
                                <p id="target-roles">Loading...</p>
                            </div>
                            <div class="meta-item">
                                <label>Status:</label>
                                <p>
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
                            </div>
                            <div class="meta-item">
                                <label>Schedule:</label>
                                <p>
                                    <?= date('M j, Y g:i A', strtotime($survey['starts_at'])) ?> - 
                                    <?= date('M j, Y g:i A', strtotime($survey['ends_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="preview-container">
                    <?php foreach ($fields as $field): ?>
                        <div class="preview-field">
                            <div class="field-header">
                                <h3><?= htmlspecialchars($field['field_label']) ?>
                                    <?php if ($field['is_required']): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            
                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" class="form-control" disabled placeholder="Text input">

                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea class="form-control" rows="4" disabled placeholder="Textarea input"></textarea>

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
                                <div class="file-preview">
                                    <input type="file" class="form-control" disabled>
                                    <small class="form-text text-muted">File upload preview</small>
                                </div>

                            <?php endif; ?>

                            <div class="field-meta">
                                <div class="meta-row">
                                    <span><strong>Type:</strong> <?= ucfirst(str_replace('_', ' ', $field['field_type'])) ?></span>
                                    <span><strong>Technical Name:</strong> <code><?= htmlspecialchars($field['field_name']) ?></code></span>
                                    <span><strong>Required:</strong> <?= $field['is_required'] ? 'Yes' : 'No' ?></span>
                                </div>
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
        
        <?php include 'includes/footer.php'; ?>
    </div>

    <script>
        // Add smooth scrolling behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Fetch roles dynamically and update the target audience
        document.addEventListener('DOMContentLoaded', function () {
            const targetRoles = <?= json_encode($target_roles) ?>;
            fetch('../api/roles.php')
                .then(response => response.json())
                .then(data => {
                    const roles = targetRoles.map(roleId => data[roleId] || `Unknown Role (${roleId})`);
                    document.getElementById('target-roles').textContent = roles.join(', ');
                })
                .catch(error => {
                    console.error('Error fetching roles:', error);
                    document.getElementById('target-roles').textContent = 'Error loading roles.';
                });
        });
    </script>
</body>
</html>