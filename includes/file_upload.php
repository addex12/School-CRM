<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_survey'])) {
        try {
            $pdo->beginTransaction();
            
            // Save survey basic info
            $title = $_POST['title'];
            $description = $_POST['description'];
            $category_id = $_POST['category_id'];
            $target_roles = json_encode($_POST['target_roles'] ?? []);
            $starts_at = $_POST['starts_at'];
            $ends_at = $_POST['ends_at'];
            $languages = json_encode($_POST['languages'] ?? ['en']);
            
            $stmt = $pdo->prepare("INSERT INTO surveys (title, description, category_id, target_roles, created_by, starts_at, ends_at, languages) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category_id, $target_roles, $_SESSION['user_id'], $starts_at, $ends_at, $languages]);
            $survey_id = $pdo->lastInsertId();
            
            // Save fields
            if (!empty($_POST['fields'])) {
                $order = 1;
                foreach ($_POST['fields'] as $field) {
                    $options = null;
                    if (in_array($field['type'], ['radio', 'checkbox', 'select', 'rating'])) {
                        $options = json_encode($field['options']);
                    }
                    
                    $validation = null;
                    if (!empty($field['validation'])) {
                        $validation = json_encode($field['validation']);
                    }
                    
                    $translations = null;
                    if (!empty($field['translations'])) {
                        $translations = json_encode($field['translations']);
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO survey_fields (survey_id, field_type, field_label, field_name, field_options, is_required, validation_rules, display_order, translations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $survey_id,
                        $field['type'],
                        $field['label'],
                        $field['name'],
                        $options,
                        $field['required'] ? 1 : 0,
                        $validation,
                        $order++,
                        $translations
                    ]);
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Survey created successfully!";
            header("Location: survey_preview.php?id=$survey_id");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error creating survey: " . $e->getMessage();
        }
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();

// Available languages
$availableLanguages = [
    'en' => 'English',
    'es' => 'Spanish',
    'fr' => 'French',
    'de' => 'German',
    'it' => 'Italian',
    'pt' => 'Portuguese',
    'ru' => 'Russian',
    'zh' => 'Chinese',
    'ja' => 'Japanese',
    'ar' => 'Arabic'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Builder - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .builder-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .form-preview {
            flex: 1;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            min-height: 500px;
        }
        .fields-panel {
            width: 300px;
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
        }
        .field-item {
            background: white;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            cursor: grab;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .field-item i {
            font-size: 1.2rem;
        }
        .form-field {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 3px;
            border: 1px dashed #ccc;
            position: relative;
        }
        .form-field:hover {
            border-color: #3498db;
        }
        .field-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 5px;
        }
        .field-actions button {
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 0.9rem;
        }
        .field-actions button:hover {
            color: #3498db;
        }
        .sortable-ghost {
            opacity: 0.5;
            background: #c8ebfb;
            border: 2px dashed #3498db;
        }
        .language-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 15px;
        }
        .language-tab {
            padding: 8px 15px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        .language-tab.active {
            border-color: #ddd;
            border-bottom-color: white;
            background: white;
            margin-bottom: -1px;
        }
        .language-content {
            display: none;
        }
        .language-content.active {
            display: block;
        }
        .tab-content {
            padding: 15px;
            background: white;
            border-radius: 0 5px 5px 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-section {
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .checkbox-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .language-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .language-selector label {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Survey Builder</h1>
            <nav>
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="surveys.php"><i class="fas fa-poll"></i> Surveys</a>
                <a href="survey_builder.php" class="active"><i class="fas fa-wrench"></i> Builder</a>
                <a href="results.php"><i class="fas fa-chart-bar"></i> Results</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>
        
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <form id="survey-form" method="POST">
                <div class="form-section">
                    <h2><i class="fas fa-info-circle"></i> Survey Information</h2>
                    
                    <div class="form-group">
                        <label for="title">Survey Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
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
                            <label>Active Languages:</label>
                            <div class="language-selector">
                                <?php foreach ($availableLanguages as $code => $name): ?>
                                    <label>
                                        <input type="checkbox" name="languages[]" value="<?php echo $code; ?>" <?php echo $code === 'en' ? 'checked' : ''; ?>>
                                        <?php echo "$name ($code)"; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Target Audience:</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="target_roles[]" value="student" checked> <i class="fas fa-user-graduate"></i> Students</label>
                            <label><input type="checkbox" name="target_roles[]" value="teacher" checked> <i class="fas fa-chalkboard-teacher"></i> Teachers</label>
                            <label><input type="checkbox" name="target_roles[]" value="parent" checked> <i class="fas fa-user-friends"></i> Parents</label>
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
                    <h2><i class="fas fa-puzzle-piece"></i> Survey Fields</h2>
                    <div class="builder-container">
                        <div class="form-preview" id="form-preview">
                            <p class="empty-message">Drag fields from the right panel to build your form</p>
                        </div>
                        
                        <div class="fields-panel">
                            <h3><i class="fas fa-toolbox"></i> Field Types</h3>
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
                    <button type="button" id="preview-btn" class="btn"><i class="fas fa-eye"></i> Preview</button>
                    <button type="submit" name="create_survey" class="btn btn-primary"><i class="fas fa-save"></i> Create Survey</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Field Configuration Modal -->
    <div id="field-modal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close-modal">&times;</span>
            <h3><i class="fas fa-cog"></i> Configure Field</h3>
            
            <div class="language-tabs" id="language-tabs">
                <!-- Tabs will be generated by JavaScript -->
            </div>
            
            <div class="tab-content">
                <form id="field-config-form">
                    <input type="hidden" id="field-type">
                    <input type="hidden" id="field-id">
                    
                    <div id="language-contents">
                        <!-- Content will be generated by JavaScript -->
                    </div>
                    
                    <div id="common-config">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="field-name">Field Name (unique):</label>
                                <input type="text" id="field-name" required pattern="[a-zA-Z0-9_]+" title="Only letters, numbers and underscores">
                                <small>No spaces or special characters (use underscore _ )</small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="field-required">
                                    Required Field
                                </label>
                            </div>
                        </div>
                        
                        <div id="options-container" class="field-options" style="display: none;">
                            <div class="form-group">
                                <label>Options:</label>
                                <div id="option-items">
                                    <!-- Options will be added here -->
                                </div>
                                <button type="button" id="add-option" class="btn"><i class="fas fa-plus"></i> Add Option</button>
                            </div>
                        </div>
                        
                        <div id="validation-container" class="field-config">
                            <h4><i class="fas fa-check-circle"></i> Validation Rules</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="validation-min">Min Value/Length:</label>
                                    <input type="number" id="validation-min" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="validation-max">Max Value/Length:</label>
                                    <input type="number" id="validation-max" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="validation-regex">Regex Pattern:</label>
                                <input type="text" id="validation-regex" placeholder="e.g. ^[A-Za-z ]+$ for letters only">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-field" class="btn"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Field</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div id="preview-modal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-eye"></i> Survey Preview</h2>
            <div id="survey-preview-content"></div>
            <div class="form-actions">
                <button type="button" class="btn close-modal"><i class="fas fa-times"></i> Close</button>
            </div>
        </div>
    </div>
    
    <!-- Template for option items -->
    <template id="option-template">
        <div class="option-item" style="display: flex; gap: 10px; margin-bottom: 5px;">
            <input type="text" class="option-value" placeholder="Option value" required>
            <button type="button" class="btn btn-delete-option"><i class="fas fa-trash"></i></button>
        </div>
    </template>
    
    <!-- Template for language tab content -->
    <template id="language-content-template">
        <div class="language-content">
            <div class="form-group">
                <label class="translation-label">Label:</label>
                <input type="text" class="translation-input" data-field="label">
            </div>
            <div id="options-translation-container" style="display: none;">
                <label>Option Translations:</label>
                <div class="option-translations">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </template>
    
    <!-- Template for option translation -->
    <template id="option-translation-template">
        <div class="form-group option-translation-item">
            <label class="option-original"></label>
            <input type="text" class="option-translation" required>
        </div>
    </template>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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