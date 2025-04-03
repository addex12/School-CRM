<?php
/**
 * Survey Builder
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Survey Builder";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get survey ID if editing
$surveyId = $_GET['id'] ?? null;
$survey = null;
$surveyFields = [];
$surveyRoles = [];

if ($surveyId) {
    // Fetch survey data
    try {
        $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($survey) {
            // Fetch survey fields
            $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
            $stmt->execute([$surveyId]);
            $surveyFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch survey roles
            $stmt = $pdo->prepare("SELECT role_id FROM survey_roles WHERE survey_id = ?");
            $stmt->execute([$surveyId]);
            $surveyRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) {
        error_log("Survey fetch error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to load survey data";
        header("Location: surveys.php");
        exit();
    }
}

// Get categories and statuses
try {
    $categories = $pdo->query("SELECT * FROM survey_categories")->fetchAll(PDO::FETCH_ASSOC);
    $statuses = $pdo->query("SELECT * FROM survey_statuses")->fetchAll(PDO::FETCH_ASSOC);
    $roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Dropdown data fetch error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load required data";
    header("Location: surveys.php");
    exit();
}

// Field types with icons
$fieldTypes = [
    'text' => ['icon' => 'fa-font', 'label' => 'Text'],
    'textarea' => ['icon' => 'fa-align-left', 'label' => 'Text Area'],
    'radio' => ['icon' => 'fa-dot-circle', 'label' => 'Radio Buttons'],
    'checkbox' => ['icon' => 'fa-check-square', 'label' => 'Checkboxes'],
    'select' => ['icon' => 'fa-caret-square-down', 'label' => 'Dropdown'],
    'number' => ['icon' => 'fa-hashtag', 'label' => 'Number'],
    'date' => ['icon' => 'fa-calendar-alt', 'label' => 'Date'],
    'rating' => ['icon' => 'fa-star', 'label' => 'Rating'],
    'file' => ['icon' => 'fa-file-upload', 'label' => 'File Upload']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Prepare survey data
        $surveyData = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description']),
            'category_id' => $_POST['category_id'] ?: null,
            'starts_at' => $_POST['starts_at'],
            'ends_at' => $_POST['ends_at'],
            'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'status_id' => $_POST['status_id'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($surveyId) {
            // Update existing survey
            $surveyData['id'] = $surveyId;
            $stmt = $pdo->prepare("UPDATE surveys SET 
                title = :title, 
                description = :description, 
                category_id = :category_id, 
                starts_at = :starts_at, 
                ends_at = :ends_at, 
                is_anonymous = :is_anonymous, 
                is_active = :is_active, 
                status_id = :status_id, 
                updated_at = :updated_at 
                WHERE id = :id");
            $stmt->execute($surveyData);
        } else {
            // Create new survey
            $surveyData['created_by'] = $_SESSION['user_id'];
            $surveyData['created_at'] = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("INSERT INTO surveys 
                (title, description, category_id, created_by, starts_at, ends_at, is_anonymous, is_active, status_id, created_at, updated_at) 
                VALUES 
                (:title, :description, :category_id, :created_by, :starts_at, :ends_at, :is_anonymous, :is_active, :status_id, :created_at, :updated_at)");
            $stmt->execute($surveyData);
            $surveyId = $pdo->lastInsertId();
        }
        
        // Handle survey fields
        $existingFieldIds = [];
        if (isset($_POST['fields'])) {
            foreach ($_POST['fields'] as $fieldData) {
                $fieldId = $fieldData['id'] ?? null;
                $fieldOptions = isset($fieldData['options']) ? json_encode($fieldData['options']) : null;
                
                $field = [
                    'survey_id' => $surveyId,
                    'field_type' => $fieldData['type'],
                    'field_label' => $fieldData['label'],
                    'field_name' => $fieldData['name'],
                    'placeholder' => $fieldData['placeholder'] ?? null,
                    'field_options' => $fieldOptions,
                    'is_required' => isset($fieldData['required']) ? 1 : 0,
                    'display_order' => $fieldData['order'],
                    'validation_rules' => isset($fieldData['validation']) ? json_encode($fieldData['validation']) : null
                ];
                
                if ($fieldId) {
                    // Update existing field
                    $field['id'] = $fieldId;
                    $stmt = $pdo->prepare("UPDATE survey_fields SET 
                        field_type = :field_type, 
                        field_label = :field_label, 
                        field_name = :field_name, 
                        placeholder = :placeholder, 
                        field_options = :field_options, 
                        is_required = :is_required, 
                        display_order = :display_order, 
                        validation_rules = :validation_rules 
                        WHERE id = :id");
                    $stmt->execute($field);
                    $existingFieldIds[] = $fieldId;
                } else {
                    // Create new field
                    $stmt = $pdo->prepare("INSERT INTO survey_fields 
                        (survey_id, field_type, field_label, field_name, placeholder, field_options, is_required, display_order, validation_rules) 
                        VALUES 
                        (:survey_id, :field_type, :field_label, :field_name, :placeholder, :field_options, :is_required, :display_order, :validation_rules)");
                    $stmt->execute($field);
                    $existingFieldIds[] = $pdo->lastInsertId();
                }
            }
        }
        
        // Delete fields that were removed
        if ($surveyId && !empty($existingFieldIds)) {
            $placeholders = implode(',', array_fill(0, count($existingFieldIds), '?'));
            $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ? AND id NOT IN ($placeholders)");
            $stmt->execute(array_merge([$surveyId], $existingFieldIds));
        }
        
        // Handle survey roles
        $selectedRoles = $_POST['roles'] ?? [];
        $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
        
        foreach ($selectedRoles as $roleId) {
            $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
            $stmt->execute([$surveyId, $roleId]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Survey " . ($surveyId ? 'updated' : 'created') . " successfully";
        header("Location: survey_view.php?id=$surveyId");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Survey save error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to save survey: " . $e->getMessage();
    }
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
    <link rel="stylesheet" href="../assets/css/survey_builder.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message"><?= $surveyId ? 'Edit Survey' : 'Create New Survey' ?></p>
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
                <form id="survey-form" method="post" class="survey-builder">
                    <!-- Survey Details Section -->
                    <div class="builder-section">
                        <div class="section-header">
                            <h2><i class="fas fa-info-circle"></i> Survey Details</h2>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Survey Title *</label>
                                <input type="text" id="title" name="title" required 
                                    value="<?= htmlspecialchars($survey['title'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($survey['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                            <?= ($survey['category_id'] ?? null) == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status_id">Status *</label>
                                <select id="status_id" name="status_id" required>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= $status['id'] ?>" 
                                            <?= ($survey['status_id'] ?? 1) == $status['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($status['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="starts_at">Start Date *</label>
                                <input type="datetime-local" id="starts_at" name="starts_at" required 
                                    value="<?= htmlspecialchars(isset($survey['starts_at']) ? date('Y-m-d\TH:i', strtotime($survey['starts_at'])) : '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="ends_at">End Date *</label>
                                <input type="datetime-local" id="ends_at" name="ends_at" required 
                                    value="<?= htmlspecialchars(isset($survey['ends_at']) ? date('Y-m-d\TH:i', strtotime($survey['ends_at'])) : '') ?>">
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="is_anonymous" value="1" 
                                        <?= ($survey['is_anonymous'] ?? 0) ? 'checked' : '' ?>>
                                    Anonymous Responses
                                </label>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" 
                                        <?= ($survey['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    Active Survey
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Survey Fields Section -->
                    <div class="builder-section">
                        <div class="section-header">
                            <h2><i class="fas fa-list-alt"></i> Survey Questions</h2>
                            <button type="button" id="add-field" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Question
                            </button>
                        </div>
                        
                        <div id="fields-container" class="fields-container">
                            <?php if (!empty($surveyFields)): ?>
                                <?php foreach ($surveyFields as $index => $field): ?>
                                    <div class="field-card" data-index="<?= $index ?>">
                                        <input type="hidden" name="fields[<?= $index ?>][id]" value="<?= $field['id'] ?>">
                                        <input type="hidden" name="fields[<?= $index ?>][order]" value="<?= $index ?>">
                                        
                                        <div class="field-header">
                                            <div class="field-type">
                                                <i class="fas <?= $fieldTypes[$field['field_type']]['icon'] ?>"></i>
                                                <?= $fieldTypes[$field['field_type']]['label'] ?>
                                            </div>
                                            <div class="field-actions">
                                                <button type="button" class="btn btn-sm btn-move-up" title="Move Up">
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-move-down" title="Move Down">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-delete-field" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="field-body">
                                            <div class="form-group">
                                                <label>Question Text *</label>
                                                <input type="text" name="fields[<?= $index ?>][label]" required 
                                                    value="<?= htmlspecialchars($field['field_label']) ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Field Name (for system use) *</label>
                                                <input type="text" name="fields[<?= $index ?>][name]" required 
                                                    value="<?= htmlspecialchars($field['field_name']) ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Placeholder Text</label>
                                                <input type="text" name="fields[<?= $index ?>][placeholder]" 
                                                    value="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                                            </div>
                                            
                                            <div class="form-group checkbox-group">
                                                <label>
                                                    <input type="checkbox" name="fields[<?= $index ?>][required]" value="1" 
                                                        <?= $field['is_required'] ? 'checked' : '' ?>>
                                                    Required Question
                                                </label>
                                            </div>
                                            
                                            <?php if (in_array($field['field_type'], ['radio', 'checkbox', 'select', 'rating'])): ?>
                                                <div class="form-group options-group">
                                                    <label>Options (one per line) *</label>
                                                    <textarea name="fields[<?= $index ?>][options]" rows="4"><?= 
                                                        $field['field_options'] ? implode("\n", json_decode($field['field_options'], true)) : '' 
                                                    ?></textarea>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="form-group">
                                                <label>Field Type</label>
                                                <select name="fields[<?= $index ?>][type]" class="field-type-select">
                                                    <?php foreach ($fieldTypes as $type => $typeData): ?>
                                                        <option value="<?= $type ?>" 
                                                            <?= $field['field_type'] === $type ? 'selected' : '' ?>>
                                                            <?= $typeData['label'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Survey Access Section -->
                    <div class="builder-section">
                        <div class="section-header">
                            <h2><i class="fas fa-users"></i> Survey Access</h2>
                        </div>
                        
                        <div class="form-group">
                            <label>Roles Allowed to Take This Survey *</label>
                            <div class="roles-grid">
                                <?php foreach ($roles as $role): ?>
                                    <div class="role-checkbox">
                                        <label>
                                            <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>" 
                                                <?= in_array($role['id'], $surveyRoles) ? 'checked' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-save"></i> Save Survey
                        </button>
                        <a href="surveys.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Field Template (hidden) -->
    <div id="field-template" class="field-card" style="display: none;">
        <input type="hidden" name="fields[__INDEX__][id]" value="">
        <input type="hidden" name="fields[__INDEX__][order]" value="__INDEX__">
        
        <div class="field-header">
            <div class="field-type">
                <i class="fas fa-font"></i> Text
            </div>
            <div class="field-actions">
                <button type="button" class="btn btn-sm btn-move-up" title="Move Up">
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-move-down" title="Move Down">
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-delete-field" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <div class="field-body">
            <div class="form-group">
                <label>Question Text *</label>
                <input type="text" name="fields[__INDEX__][label]" required>
            </div>
            
            <div class="form-group">
                <label>Field Name (for system use) *</label>
                <input type="text" name="fields[__INDEX__][name]" required>
            </div>
            
            <div class="form-group">
                <label>Placeholder Text</label>
                <input type="text" name="fields[__INDEX__][placeholder]">
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="fields[__INDEX__][required]" value="1">
                    Required Question
                </label>
            </div>
            
            <div class="form-group options-group" style="display: none;">
                <label>Options (one per line) *</label>
                <textarea name="fields[__INDEX__][options]" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label>Field Type</label>
                <select name="fields[__INDEX__][type]" class="field-type-select">
                    <?php foreach ($fieldTypes as $type => $typeData): ?>
                        <option value="<?= $type ?>"><?= $typeData['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../assets/js/survey_builder.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const addFieldButton = document.getElementById('add-field');
        const fieldsContainer = document.getElementById('fields-container');
        const fieldTemplate = document.getElementById('field-template').innerHTML;

        let fieldIndex = <?= count($surveyFields) ?>; // Start index after existing fields

        addFieldButton.addEventListener('click', function () {
            const newFieldHtml = fieldTemplate.replace(/__INDEX__/g, fieldIndex);
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newFieldHtml.trim();
            fieldsContainer.appendChild(tempDiv.firstChild);
            fieldIndex++;
        });

        fieldsContainer.addEventListener('click', function (event) {
            if (event.target.closest('.btn-delete-field')) {
                const fieldCard = event.target.closest('.field-card');
                fieldCard.remove();
            }
        });
    });
</script>
</body>
</html>