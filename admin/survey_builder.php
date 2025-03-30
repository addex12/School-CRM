<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Fetch roles excluding admin
$roles = $pdo->query("SELECT id, role_name FROM roles WHERE role_name != 'admin' ORDER BY role_name")->fetchAll();

// Fetch categories
$categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();

// Define status options
$statusOptions = [
    ['value' => 'draft', 'label' => 'Draft'],
    ['value' => 'active', 'label' => 'Active'],
    ['value' => 'inactive', 'label' => 'Inactive'],
    ['value' => 'archived', 'label' => 'Archived']
];

// Define field types
$fieldTypes = [
    'text' => 'Text Input',
    'textarea' => 'Text Area',
    'radio' => 'Multiple Choice',
    'checkbox' => 'Checkboxes',
    'select' => 'Dropdown',
    'number' => 'Number',
    'date' => 'Date',
    'rating' => 'Rating',
    'file' => 'File Upload'
];

// Fetch survey and fields if editing
$survey_id = $_GET['survey_id'] ?? null;
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validate and process form data
        $formData = [
            'title' => htmlspecialchars($_POST['title']),
            'description' => htmlspecialchars($_POST['description']),
            'category_id' => intval($_POST['category_id']),
            'target_roles' => json_encode($_POST['target_roles'] ?? []),
            'status' => in_array($_POST['status'], array_column($statusOptions, 'value')) ? $_POST['status'] : 'draft',
            'starts_at' => date('Y-m-d H:i:s', strtotime($_POST['starts_at'])),
            'ends_at' => date('Y-m-d H:i:s', strtotime($_POST['ends_at'])),
            'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0
        ];

        if ($formData['starts_at'] >= $formData['ends_at']) {
            throw new Exception("Start date must be before end date.");
        }

        if ($survey_id) {
            // Update survey
            $stmt = $pdo->prepare("UPDATE surveys SET 
                title = ?, description = ?, category_id = ?, target_roles = ?, status = ?, 
                starts_at = ?, ends_at = ?, is_anonymous = ? WHERE id = ?");
            $stmt->execute(array_values($formData + [$survey_id]));
        } else {
            // Insert new survey
            $stmt = $pdo->prepare("INSERT INTO surveys 
                (title, description, category_id, target_roles, status, starts_at, ends_at, is_anonymous, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_values($formData + [$_SESSION['user_id']]));
            $survey_id = $pdo->lastInsertId();
        }

        // Save survey fields
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
                'field_' . ($index + 1),
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
    <link rel="stylesheet" href="../assets/css/forms.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="survey-builder">
            <header class="builder-header">
                <h1><i class="fas fa-poll-h"></i> <?= $survey_id ? 'Edit Survey' : 'Create New Survey' ?></h1>
                <div class="form-actions">
                    <button type="submit" form="survey-form" class="btn-success">
                        <i class="fas fa-save"></i> Save Survey
                    </button>
                    <a href="surveys.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </header>

            <?php include 'includes/alerts.php'; ?>

            <form id="survey-form" method="POST" class="builder-form">
                <!-- Basic Information Section -->
                <section class="form-section card">
                    <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                    <div class="grid-col-2">
                        <div class="form-group">
                            <label for="title">Survey Title</label>
                            <input type="text" id="title" name="title" required
                                   value="<?= htmlspecialchars($survey['title'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($survey['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($survey['description'] ?? '') ?></textarea>
                    </div>
                </section>

                <!-- Settings Section -->
                <section class="form-section card">
                    <h2><i class="fas fa-cog"></i> Settings</h2>
                    <div class="grid-col-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?= $opt['value'] ?>" <?= ($survey['status'] ?? 'draft') === $opt['value'] ? 'selected' : '' ?>>
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

                    <div class="grid-col-2">
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
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_anonymous" <?= ($survey['is_anonymous'] ?? 0) ? 'checked' : '' ?>>
                                Anonymous Responses
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Questions Section -->
                <section class="form-section card">
                    <h2><i class="fas fa-question-circle"></i> Questions</h2>
                    <div id="questions-container">
                        <?php foreach ($fields as $index => $field): ?>
                        <div class="question-card">
                            <!-- Question input fields -->
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-question" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </section>
            </form>
        </main>
    </div>

    <script src="../assets/js/survey-builder.js"></script>
</body>
</html>