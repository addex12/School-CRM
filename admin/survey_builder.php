<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_survey'])) {
        // Save survey basic info
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $target_roles = json_encode($_POST['target_roles']);
        $starts_at = $_POST['starts_at'];
        $ends_at = $_POST['ends_at'];
        
        $stmt = $pdo->prepare("INSERT INTO surveys (title, description, category_id, target_roles, created_by, starts_at, ends_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $category_id, $target_roles, $_SESSION['user_id'], $starts_at, $ends_at]);
        $survey_id = $pdo->lastInsertId();
        
        // Save fields
        if (!empty($_POST['fields'])) {
            foreach ($_POST['fields'] as $field) {
                $options = null;
                if (in_array($field['type'], ['radio', 'checkbox', 'select'])) {
                    $options = json_encode(explode("\n", $field['options']));
                }
                
                $validation = null;
                if (!empty($field['validation'])) {
                    $validation = json_encode($field['validation']);
                }
                
                $stmt = $pdo->prepare("INSERT INTO survey_fields (survey_id, field_type, field_label, field_name, field_options, is_required, validation_rules, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $survey_id,
                    $field['type'],
                    $field['label'],
                    $field['name'],
                    $options,
                    isset($field['required']) ? 1 : 0,
                    $validation,
                    $field['order']
                ]);
            }
        }
        
        $_SESSION['success'] = "Survey created successfully!";
        header("Location: survey_preview.php?id=$survey_id");
        exit();
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Builder - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="survey_builder.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
<?php require_once 'includes/header.php'; ?>
        </header>
        
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <form id="survey-form" method="POST">
                <div class="form-section">
                    <h2>Survey Information</h2>
                    <div class="form-group">
                        <label for="title">Survey Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Target Audience:</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="target_roles[]" value="student" checked> Students</label>
                            <label><input type="checkbox" name="target_roles[]" value="teacher" checked> Teachers</label>
                            <label><input type="checkbox" name="target_roles[]" value="parent" checked> Parents</label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="starts_at">Start Date/Time:</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="ends_at">End Date/Time:</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Survey Fields</h2>
                    <div class="builder-container">
                        <div class="form-preview" id="form-preview">
                            <p class="empty-message">Drag fields from the right panel to build your form</p>
                        </div>
                        
                        <div class="fields-panel" id="fields-panel">
                            <h3>Available Fields</h3>
                            <div class="field-item" data-type="text">
                                <i class="fas fa-font"></i> Text Input
                            </div>
                            <div class="field-item" data-type="textarea">
                                <i class="fas fa-align-left"></i> Text Area
                            </div>
                            <div class="field-item" data-type="radio">
                                <i class="far fa-dot-circle"></i> Radio Buttons
                            </div>
                            <div class="field-item" data-type="checkbox">
                                <i class="far fa-check-square"></i> Checkboxes
                            </div>
                            <div class="field-item" data-type="select">
                                <i class="fas fa-caret-down"></i> Dropdown
                            </div>
                            <div class="field-item" data-type="number">
                                <i class="fas fa-hashtag"></i> Number
                            </div>
                            <div class="field-item" data-type="date">
                                <i class="far fa-calendar-alt"></i> Date
                            </div>
                            <div class="field-item" data-type="rating">
                                <i class="fas fa-star"></i> Rating
                            </div>
                            <div class="field-item" data-type="file">
                                <i class="fas fa-file-upload"></i> File Upload
                            </div>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" id="fields-data" name="fields">
                <div class="form-actions">
                    <button type="button" id="preview-btn" class="btn">Preview</button>
                    <button type="submit" name="create_survey" class="btn btn-primary">Create Survey</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Field Configuration Modal -->
    <div id="field-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Configure Field</h3>
            <form id="field-config-form">
                <input type="hidden" id="field-type">
                <input type="hidden" id="field-id">
                
                <div class="form-group">
                    <label for="field-label">Label:</label>
                    <input type="text" id="field-label" required>
                </div>
                
                <div class="form-group">
                    <label for="field-name">Field Name (unique):</label>
                    <input type="text" id="field-name" required>
                    <small>No spaces or special characters (use underscore _ )</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="field-required">
                        Required Field
                    </label>
                </div>
                
                <div id="options-container" class="field-options">
                    <div class="form-group">
                        <label for="field-options">Options (one per line):</label>
                        <textarea id="field-options" rows="4"></textarea>
                    </div>
                </div>
                
                <div id="validation-container" class="field-config">
                    <h4>Validation Rules</h4>
                    <div class="form-group">
                        <label for="validation-min">Min Value/Length:</label>
                        <input type="number" id="validation-min">
                    </div>
                    
                    <div class="form-group">
                        <label for="validation-max">Max Value/Length:</label>
                        <input type="number" id="validation-max">
                    </div>
                    
                    <div class="form-group">
                        <label for="validation-regex">Regex Pattern:</label>
                        <input type="text" id="validation-regex">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancel-field" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Field</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div id="preview-modal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close-modal">&times;</span>
            <h2>Survey Preview</h2>
            <div id="survey-preview-content"></div>
            <div class="form-actions">
                <button type="button" class="btn close-modal">Close</button>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script src="../assets/js/survey_builder.js"></script>
    <script>
        // Initialize date/time inputs with current time
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            
            document.getElementById('starts_at').value = localISOTime;
            document.getElementById('ends_at').value = localISOTime;
        });
    </script>
</body>
</html>