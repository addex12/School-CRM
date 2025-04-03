<?php
/**
 * Edit Survey
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Edit Survey";

// Check if survey ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Survey ID not specified.";
    header("Location: surveys.php");
    exit();
}

$surveyId = (int)$_GET['id'];

// Fetch survey data
try {
    $stmt = $pdo->prepare("SELECT s.*, u.username as creator_name 
                          FROM surveys s
                          JOIN users u ON s.created_by = u.id
                          WHERE s.id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$survey) {
        $_SESSION['error'] = "Survey not found.";
        header("Location: surveys.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: surveys.php");
    exit();
}

// Fetch survey fields
try {
    $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
    $stmt->execute([$surveyId]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to load survey fields.";
    $fields = [];
}

// Fetch categories and statuses for dropdowns
$categories = $pdo->query("SELECT * FROM survey_categories")->fetchAll(PDO::FETCH_ASSOC);
$statuses = $pdo->query("SELECT * FROM survey_statuses")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $categoryId = (int)$_POST['category_id'];
    $statusId = (int)$_POST['status_id'];
    $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $startsAt = $_POST['starts_at'];
    $endsAt = $_POST['ends_at'];
    
    // Basic validation
    if (empty($title)) {
        $_SESSION['error'] = "Survey title is required.";
    } elseif (empty($startsAt) || empty($endsAt)) {
        $_SESSION['error'] = "Start and end dates are required.";
    } elseif (strtotime($endsAt) <= strtotime($startsAt)) {
        $_SESSION['error'] = "End date must be after start date.";
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Update survey
            $stmt = $pdo->prepare("UPDATE surveys SET 
                                  title = ?, description = ?, category_id = ?, status_id = ?,
                                  is_anonymous = ?, starts_at = ?, ends_at = ?, updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([
                $title, $description, $categoryId, $statusId,
                $isAnonymous, $startsAt, $endsAt, $surveyId
            ]);
            
            // Handle fields update
            if (isset($_POST['fields'])) {
                foreach ($_POST['fields'] as $fieldId => $fieldData) {
                    $stmt = $pdo->prepare("UPDATE survey_fields SET 
                                          field_label = ?, field_type = ?, is_required = ?,
                                          display_order = ?, field_options = ?
                                          WHERE id = ? AND survey_id = ?");
                    $options = isset($fieldData['options']) ? explode("\n", trim($fieldData['options'])) : [];
                    $stmt->execute([
                        $fieldData['label'], $fieldData['type'], isset($fieldData['required']) ? 1 : 0,
                        $fieldData['order'], json_encode($options), $fieldId, $surveyId
                    ]);
                }
            }
            
            // Handle new fields
            if (isset($_POST['new_fields'])) {
                foreach ($_POST['new_fields'] as $newField) {
                    if (!empty($newField['label'])) {
                        $stmt = $pdo->prepare("INSERT INTO survey_fields 
                                              (survey_id, field_label, field_type, is_required, 
                                              display_order, field_options)
                                              VALUES (?, ?, ?, ?, ?, ?)");
                        $options = isset($newField['options']) ? explode("\n", trim($newField['options'])) : [];
                        $stmt->execute([
                            $surveyId, $newField['label'], $newField['type'], 
                            isset($newField['required']) ? 1 : 0,
                            $newField['order'], json_encode($options)
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Survey updated successfully!";
            header("Location: survey_view.php?id=$surveyId");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to update survey: " . $e->getMessage();
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/survey_form.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Editing: <?= htmlspecialchars($survey['title']) ?></p>
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
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?= $_SESSION['error'] ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="survey-form">
                    <div class="form-section">
                        <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                        
                        <div class="form-group">
                            <label for="title">Survey Title*</label>
                            <input type="text" id="title" name="title" required 
                                   value="<?= htmlspecialchars($survey['title']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($survey['description']) ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                            <?= $category['id'] == $survey['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status_id">Status</label>
                                <select id="status_id" name="status_id" required>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= $status['id'] ?>" 
                                            <?= $status['id'] == $survey['status_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($status['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="starts_at">Start Date*</label>
                                <input type="datetime-local" id="starts_at" name="starts_at" required 
                                       value="<?= date('Y-m-d\TH:i', strtotime($survey['starts_at'])) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="ends_at">End Date*</label>
                                <input type="datetime-local" id="ends_at" name="ends_at" required 
                                       value="<?= date('Y-m-d\TH:i', strtotime($survey['ends_at'])) ?>">
                            </div>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="is_anonymous" name="is_anonymous" 
                                   <?= $survey['is_anonymous'] ? 'checked' : '' ?>>
                            <label for="is_anonymous">Allow anonymous responses</label>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2><i class="fas fa-question-circle"></i> Survey Questions</h2>
                        
                        <div id="survey-fields">
                            <?php foreach ($fields as $field): ?>
                                <div class="field-card" data-field-id="<?= $field['id'] ?>">
                                    <div class="field-header">
                                        <h3>Question #<span class="field-order"><?= $field['display_order'] ?></span></h3>
                                        <button type="button" class="btn btn-sm btn-delete remove-field">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    
                                    <input type="hidden" name="fields[<?= $field['id'] ?>][id]" value="<?= $field['id'] ?>">
                                    
                                    <div class="form-group">
                                        <label>Question Text*</label>
                                        <input type="text" name="fields[<?= $field['id'] ?>][label]" required 
                                               value="<?= htmlspecialchars($field['field_label']) ?>">
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Question Type*</label>
                                            <select name="fields[<?= $field['id'] ?>][type]" class="field-type" required>
                                                <option value="text" <?= $field['field_type'] === 'text' ? 'selected' : '' ?>>Text</option>
                                                <option value="textarea" <?= $field['field_type'] === 'textarea' ? 'selected' : '' ?>>Paragraph</option>
                                                <option value="radio" <?= $field['field_type'] === 'radio' ? 'selected' : '' ?>>Multiple Choice</option>
                                                <option value="checkbox" <?= $field['field_type'] === 'checkbox' ? 'selected' : '' ?>>Checkboxes</option>
                                                <option value="select" <?= $field['field_type'] === 'select' ? 'selected' : '' ?>>Dropdown</option>
                                                <option value="number" <?= $field['field_type'] === 'number' ? 'selected' : '' ?>>Number</option>
                                                <option value="date" <?= $field['field_type'] === 'date' ? 'selected' : '' ?>>Date</option>
                                                <option value="rating" <?= $field['field_type'] === 'rating' ? 'selected' : '' ?>>Rating</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Display Order*</label>
                                            <input type="number" name="fields[<?= $field['id'] ?>][order]" min="1" required 
                                                   value="<?= $field['display_order'] ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group checkbox-group">
                                        <input type="checkbox" id="required_<?= $field['id'] ?>" 
                                               name="fields[<?= $field['id'] ?>][required]"
                                               <?= $field['is_required'] ? 'checked' : '' ?>>
                                        <label for="required_<?= $field['id'] ?>">Required question</label>
                                    </div>
                                    
                                    <div class="form-group options-group" 
                                         style="<?= in_array($field['field_type'], ['radio', 'checkbox', 'select']) ? '' : 'display: none;' ?>">
                                        <label>Options (one per line)*</label>
                                        <textarea name="fields[<?= $field['id'] ?>][options]" rows="3"><?= 
                                            !empty($field['field_options']) ? implode("\n", json_decode($field['field_options'])) : '' 
                                        ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" id="add-field" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="survey_view.php?id=<?= $surveyId ?>" class="btn btn-outline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Template for new fields -->
    <template id="field-template">
        <div class="field-card" data-new-field>
            <div class="field-header">
                <h3>New Question</h3>
                <button type="button" class="btn btn-sm btn-delete remove-field">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
            
            <div class="form-group">
                <label>Question Text*</label>
                <input type="text" name="new_fields[][label]" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Question Type*</label>
                    <select name="new_fields[][type]" class="field-type" required>
                        <option value="text">Text</option>
                        <option value="textarea">Paragraph</option>
                        <option value="radio">Multiple Choice</option>
                        <option value="checkbox">Checkboxes</option>
                        <option value="select">Dropdown</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="rating">Rating</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Display Order*</label>
                    <input type="number" name="new_fields[][order]" min="1" required>
                </div>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="new_fields[][required]">
                <label>Required question</label>
            </div>
            
            <div class="form-group options-group" style="display: none;">
                <label>Options (one per line)*</label>
                <textarea name="new_fields[][options]" rows="3"></textarea>
            </div>
        </div>
    </template>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/survey_form.js"></script>
</body>
</html>