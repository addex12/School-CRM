<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../models/Survey.php';
requireAdmin();

$pageTitle = "Survey Builder";

try {
    $roles = $pdo->query("SELECT id, role_name FROM roles WHERE role_name != 'admin' ORDER BY role_name")->fetchAll();
    $categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();
    $statusOptions = Survey::getStatuses();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load survey data. Please try again.";
    header("Location: surveys.php");
    exit();
}

$fieldTypes = [
    'text' => ['icon' => 'fa-font', 'label' => 'Text Input'],
    'textarea' => ['icon' => 'fa-paragraph', 'label' => 'Text Area'],
    'radio' => ['icon' => 'fa-dot-circle', 'label' => 'Multiple Choice'],
    'checkbox' => ['icon' => 'fa-check-square', 'label' => 'Checkboxes'],
    'select' => ['icon' => 'fa-caret-down', 'label' => 'Dropdown'],
    'number' => ['icon' => 'fa-hashtag', 'label' => 'Number'],
    'date' => ['icon' => 'fa-calendar', 'label' => 'Date'],
    'rating' => ['icon' => 'fa-star', 'label' => 'Rating'],
    'file' => ['icon' => 'fa-file-upload', 'label' => 'File Upload']
];

$survey_id = $_GET['survey_id'] ?? $_POST['survey_id'] ?? null;
$survey = null;
$fields = [];

// Handle delete survey
if (isset($_POST['delete_survey'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
        $stmt->execute([$_POST['survey_id']]);
        $_SESSION['success'] = "Survey deleted successfully!";
        header("Location: surveys.php");
        exit();
    } catch (PDOException $e) {
        error_log("Delete survey error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete survey.";
    }
}

// Fetch survey data for editing
if ($survey_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->execute([$survey_id]);
        $survey = $stmt->fetch();

        if (!$survey) {
            $_SESSION['error'] = "Survey not found!";
            header("Location: surveys.php");
            exit();
        }

        $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
        $stmt->execute([$survey_id]);
        $fields = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Survey fetch error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to load survey data.";
    }
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    try {
        $pdo->beginTransaction();

        $formData = [
            'title' => htmlspecialchars($_POST['title']),
            'description' => htmlspecialchars($_POST['description'] ?? ''),
            'category_id' => intval($_POST['category_id']),
            'target_roles' => json_encode($_POST['target_roles'] ?? []),
            'status' => in_array($_POST['status'], array_column($statusOptions, 'value')) ? $_POST['status'] : 'draft',
            'starts_at' => date('Y-m-d 00:00:00', strtotime($_POST['starts_at'])),
            'ends_at' => date('Y-m-d 23:59:59', strtotime($_POST['ends_at'])),
            'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0
        ];

        if ($formData['starts_at'] >= $formData['ends_at']) {
            throw new Exception("Start date must be before end date.");
        }

        if ($survey_id) {
            $stmt = $pdo->prepare("UPDATE surveys SET 
                title = ?, description = ?, category_id = ?, target_roles = ?, status = ?, 
                starts_at = ?, ends_at = ?, is_anonymous = ? WHERE id = ?");
            $stmt->execute(array_merge(array_values($formData), [$survey_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO surveys 
                (title, description, category_id, target_roles, status, starts_at, ends_at, is_anonymous, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_merge(array_values($formData), [$_SESSION['user_id']]);
            $survey_id = $pdo->lastInsertId();
        }

        // Process questions
        $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?")->execute([$survey_id]);
        
        if (isset($_POST['questions'])) {
            foreach ($_POST['questions'] as $index => $question) {
                $options = in_array($_POST['field_types'][$index], ['radio', 'checkbox', 'select']) 
                    ? json_encode(array_map('trim', explode(',', $_POST['options'][$index] ?? '')))
                    : null;

                $stmt = $pdo->prepare("INSERT INTO survey_fields 
                    (survey_id, field_type, field_label, field_name, placeholder, field_options, is_required, display_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $survey_id,
                    $_POST['field_types'][$index],
                    htmlspecialchars($question),
                    'field_'.($index+1),
                    $_POST['placeholders'][$index] ?? '',
                    $options,
                    isset($_POST['required'][$index]) ? 1 : 0,
                    $index
                ]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Survey saved successfully!";
        header("Location: survey_preview.php?id=$survey_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Survey save error: " . $e->getMessage());
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($survey_id ? 'Edit Survey' : 'Create New Survey') ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/survey_builder.css">
</head>
<body>
<div class="admin-dashboard">
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-header">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </header>

        <div class="content">
            <header class="builder-header">
                <h1><i class="fas fa-poll-h"></i> <?= $survey_id ? 'Edit Survey' : 'Create New Survey' ?></h1>
                <div class="form-actions">
                    <button type="submit" form="survey-form" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Survey
                    </button>
                    <a href="surveys.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </header>

            <?php include 'includes/alerts.php'; ?>

            <form id="survey-form" method="POST">
                <input type="hidden" name="survey_id" value="<?= htmlspecialchars($survey_id) ?>">
                
                <!-- Basic Information Section -->
                <section class="form-section">
                    <h2><i class="fas fa-info-circle text-primary"></i> Basic Information</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Survey Title</label>
                            <input type="text" name="title" required value="<?= htmlspecialchars($survey['title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($survey['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"><?= htmlspecialchars($survey['description'] ?? '') ?></textarea>
                    </div>
                </section>

                <!-- Settings Section -->
                <section class="form-section">
                    <h2><i class="fas fa-cog text-primary"></i> Settings</h2>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <?php foreach ($statusOptions as $opt): ?>
                                    <option value="<?= $opt['value'] ?>" <?= ($survey['status'] ?? 'draft') === $opt['value'] ? 'selected' : '' ?>>
                                        <i class="fas <?= $opt['icon'] ?>"></i>
                                        <?= htmlspecialchars($opt['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="starts_at" required value="<?= isset($survey['starts_at']) ? date('Y-m-d', strtotime($survey['starts_at'])) : date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="ends_at" required value="<?= isset($survey['ends_at']) ? date('Y-m-d', strtotime($survey['ends_at'])) : '' ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Target Audience</label>
                            <select name="target_roles[]" multiple required>
                                <?php
                                $targetRoles = isset($survey['target_roles']) ? json_decode($survey['target_roles'], true) : [];
                                $targetRoles = is_array($targetRoles) ? $targetRoles : [];
                                ?>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= in_array($role['id'], $targetRoles) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="switch-container">
                                <label class="switch">
                                    <input type="checkbox" name="is_anonymous" <?= ($survey['is_anonymous'] ?? 0) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                    <span class="switch-label">Anonymous</span>
                                    <span class="switch-description">Allow users to submit responses anonymously.</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Questions Section -->
                <section class="form-section">
                    <h2><i class="fas fa-question-circle text-primary"></i> Questions</h2>
                    <div id="questions-container">
                        <?php foreach ($fields as $index => $field): ?>
                        <div class="question-card">
                            <div class="form-group">
                                <label>Question</label>
                                <input type="text" name="questions[]" value="<?= htmlspecialchars($field['field_label']) ?>" required />
                            </div>
                            <div class="form-group">
                                <label>Field Type</label>
                                <select name="field_types[]" class="field-type-select" required>
                                    <?php foreach ($fieldTypes as $type => $details): ?>
                                    <option value="<?= $type ?>" <?= $field['field_type'] === $type ? 'selected' : '' ?>>
                                        <?= $details['label'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group options-group" style="<?= !in_array($field['field_type'], ['radio', 'checkbox', 'select']) ? 'display:none' : '' ?>">
                                <label>Options (comma-separated)</label>
                                <input type="text" name="options[]" value="<?= 
                                    $field['field_options'] ? htmlspecialchars(implode(',', json_decode($field['field_options']))) : '' 
                                ?>">
                            </div>
                            <div class="form-group">
                                <label>Placeholder</label>
                                <input type="text" name="placeholders[]" value="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="required-check">
                                    <input type="checkbox" name="required[<?= $index ?>]" <?= $field['is_required'] ? 'checked' : '' ?>>
                                    Required
                                </label>
                            </div>
                            <button type="button" class="remove-question btn btn-danger">
                                <i class="fas fa-trash"></i> Remove Question
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-question" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </section>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/survey_builder.js"></script>
<script src="../assets/js/field_type.js"></script>
<script src="../assets/js/validation.js"></script>
</body>
</html>
<?php include 'includes/footer.php'; ?>
