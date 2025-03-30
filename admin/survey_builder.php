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

// Database queries and configurations
$roles = $pdo->query("SELECT id, role_name FROM roles WHERE role_name != 'admin' ORDER BY role_name")->fetchAll();
$categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();

$statusOptions = [
    ['value' => 'draft', 'label' => 'Draft', 'icon' => 'fa-file'],
    ['value' => 'active', 'label' => 'Active', 'icon' => 'fa-rocket'],
    ['value' => 'inactive', 'label' => 'Inactive', 'icon' => 'fa-pause'],
    ['value' => 'archived', 'label' => 'Archived', 'icon' => 'fa-archive']
];

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

// Survey data handling
$survey_id = $_GET['survey_id'] ?? $_POST['survey_id'] ?? null;
$survey = null;
$fields = [];

if ($survey_id) {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch();

    if ($survey) {
        $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
        $stmt->execute([$survey_id]);
        $fields = $stmt->fetchAll();
    }
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Generate a unique random survey ID for new surveys
        if (!$survey_id) {
            do {
                $randomSurveyId = bin2hex(random_bytes(4)); // Generate a random 8-character ID
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE id = ?");
                $stmt->execute([$randomSurveyId]);
                $isUnique = $stmt->fetchColumn() == 0;
            } while (!$isUnique);
        }

        $formData = [
            'id' => $randomSurveyId ?? $survey_id,
            'title' => htmlspecialchars($_POST['title']),
            'description' => htmlspecialchars($_POST['description']),
            'category_id' => intval($_POST['category_id']),
            'target_roles' => json_encode(is_array($_POST['target_roles'] ?? null) ? $_POST['target_roles'] : []), // Ensure target_roles is an array
            'status' => in_array($_POST['status'], array_column($statusOptions, 'value')) ? $_POST['status'] : 'draft',
            'starts_at' => date('Y-m-d H:i:s', strtotime($_POST['starts_at'])),
            'ends_at' => date('Y-m-d H:i:s', strtotime($_POST['ends_at'])),
            'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0
        ];

        if ($formData['starts_at'] >= $formData['ends_at']) {
            throw new Exception("Start date must be before end date.");
        }

        if ($survey_id) {
            $stmt = $pdo->prepare("UPDATE surveys SET 
                title = ?, description = ?, category_id = ?, target_roles = ?, status = ?, 
                starts_at = ?, ends_at = ?, is_anonymous = ? WHERE id = ?");
            $stmt->execute(array_values($formData));
        } else {
            $stmt = $pdo->prepare("INSERT INTO surveys 
                (id, title, description, category_id, target_roles, status, starts_at, ends_at, is_anonymous, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_values($formData + [$_SESSION['user_id']]));
            $survey_id = $formData['id'];
        }

        // Process questions
        $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?")->execute([$survey_id]);
        foreach ($_POST['questions'] as $index => $question) {
            $options = in_array($_POST['field_types'][$index], ['radio', 'checkbox', 'select']) 
                ? json_encode(array_map('trim', explode(',', $_POST['options'][$index])))
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

        $pdo->commit();
        $_SESSION['success'] = "Survey saved successfully!";
        header("Location: survey_preview.php?id=$survey_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $survey_id ? 'Edit Survey' : 'New Survey' ?> - Survey Builder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="survey_builder.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="survey-builder">
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
                            <input type="text" name="title" required
                                   value="<?= htmlspecialchars($survey['title'] ?? '') ?>">
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
                                    <?= $opt['label'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="datetime-local" name="starts_at" required
                                   value="<?= isset($survey['starts_at']) ? date('Y-m-d\TH:i', strtotime($survey['starts_at'])) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="datetime-local" name="ends_at" required
                                   value="<?= isset($survey['ends_at']) ? date('Y-m-d\TH:i', strtotime($survey['ends_at'])) : '' ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Target Audience</label>
                            <div class="role-grid">
                                <?php $targetRoles = isset($survey['target_roles']) ? json_decode($survey['target_roles'], true) : []; ?>
                                <?php foreach ($roles as $role): ?>
                                <div class="role-card">
                                    <input type="checkbox" name="target_roles[]" 
                                           id="role-<?= $role['id'] ?>" value="<?= $role['id'] ?>"
                                           <?= in_array($role['id'], $targetRoles) ? 'checked' : '' ?>>
                                    <label for="role-<?= $role['id'] ?>">
                                        <i class="fas fa-users"></i>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="switch-container">
                                <label class="switch">
                                    <input type="checkbox" name="is_anonymous" <?= ($survey['is_anonymous'] ?? 0) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                                <span>Anonymous Responses</span>
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
                                <input type="text" name="options[]" value="<?= htmlspecialchars($field['field_options'] ?? '') ?>">
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
        </main>
    </div>

    <script src="../assets/js/survey_builder.js"></script></body>
    <div class="form-actions">
                    <button type="submit" form="survey-form" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Survey
                    </button>
                    <a href="surveys.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
</html>