<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Load configuration from JSON
$configPath = '../assets/config/survey-config.json';
$config = json_decode(file_get_contents($configPath), true);

// Check if editing an existing survey
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
    } else {
        $_SESSION['error'] = "Survey not found.";
        header("Location: surveys.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $status = $_POST['status'] ?? $config['defaultSettings']['status'];
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

        // Validate survey dates
        if (strtotime($_POST['starts_at']) >= strtotime($_POST['ends_at'])) {
            throw new Exception("Start date must be earlier than the end date.");
        }

        if ($survey_id) {
            // Update existing survey
            $stmt = $pdo->prepare("UPDATE surveys SET 
                title = ?, description = ?, category_id = ?, target_roles = ?, status = ?, 
                starts_at = ?, ends_at = ?, is_anonymous = ? WHERE id = ?");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['category_id'],
                json_encode($_POST['target_roles']),
                $status,
                $_POST['starts_at'],
                $_POST['ends_at'],
                $is_anonymous,
                $survey_id
            ]);

            // Delete existing fields
            $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
            $stmt->execute([$survey_id]);
        } else {
            // Insert new survey
            $stmt = $pdo->prepare("INSERT INTO surveys 
                (title, description, category_id, target_roles, status, starts_at, ends_at, is_anonymous, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['category_id'],
                json_encode($_POST['target_roles']),
                $status,
                $_POST['starts_at'],
                $_POST['ends_at'],
                $is_anonymous,
                $_SESSION['user_id']
            ]);
            $survey_id = $pdo->lastInsertId();
        }

        // Save fields
        if (!empty($_POST['questions'])) {
            foreach ($_POST['questions'] as $index => $question) {
                $options = null;
                if (in_array($_POST['field_types'][$index], ['radio', 'checkbox', 'dropdown'])) {
                    $options = json_encode(array_map('trim', explode(',', $_POST['options'][$index])));
                }

                $stmt = $pdo->prepare("INSERT INTO survey_fields 
                    (survey_id, field_type, field_label, field_name, placeholder, field_options, is_required, display_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $survey_id,
                    $_POST['field_types'][$index],
                    $question,
                    'field_' . ($index + 1),
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
        $_SESSION['error'] = "Error saving survey: " . $e->getMessage();
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $survey_id ? 'Edit Survey' : 'Create New Survey'; ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <h1><?php echo $survey_id ? 'Edit Survey' : 'Create New Survey'; ?></h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <form id="survey-form" method="POST">
                <div class="form-section">
                    <h2><i class="fas fa-info-circle"></i> Survey Information</h2>
                    
                    <div class="form-group">
                        <label for="title">Survey Title</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($survey['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php 
                            echo htmlspecialchars($survey['description'] ?? ''); 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php 
                                    echo isset($survey['category_id']) && $survey['category_id'] == $category['id'] ? 'selected' : ''; 
                                ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Target Audience</label>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <?php 
                            $targetRoles = isset($survey['target_roles']) ? json_decode($survey['target_roles'], true) : [];
                            foreach ($config['targetRoles'] as $role): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="target_roles[]" value="<?php echo $role['value']; ?>" <?php 
                                        echo in_array($role['value'], $targetRoles) ? 'checked' : ''; 
                                    ?>>
                                    <?php echo $role['label']; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="starts_at">Start Date/Time</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" 
                                   value="<?php echo isset($survey['starts_at']) ? date('Y-m-d\TH:i', strtotime($survey['starts_at'])) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="ends_at">End Date/Time</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" 
                                   value="<?php echo isset($survey['ends_at']) ? date('Y-m-d\TH:i', strtotime($survey['ends_at'])) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <?php foreach ($config['statusOptions'] as $option): ?>
                                <option value="<?php echo $option['value']; ?>" <?php 
                                    echo isset($survey['status']) && $survey['status'] === $option['value'] ? 'selected' : ''; 
                                ?>>
                                    <?php echo $option['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_anonymous" name="is_anonymous" <?php 
                                echo isset($survey['is_anonymous']) && $survey['is_anonymous'] ? 'checked' : ''; 
                            ?>>
                            Make this survey anonymous
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-question-circle"></i> Survey Questions</h2>
                    <div id="survey-fields">
                        <?php if (!empty($fields)): ?>
                            <?php foreach ($fields as $index => $field): ?>
                                <div class="survey-field">
                                    <div class="form-group">
                                        <label for="question-<?php echo $index + 1; ?>">Question <?php echo $index + 1; ?></label>
                                        <input type="text" name="questions[]" id="question-<?php echo $index + 1; ?>" 
                                               class="form-control" value="<?php echo htmlspecialchars($field['field_label']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Field Type</label>
                                        <select name="field_types[]" class="form-control field-type-selector" required>
                                            <?php foreach ($config['fieldTypes'] as $type => $label): ?>
                                                <option value="<?php echo $type; ?>" <?php 
                                                    echo $field['field_type'] === $type ? 'selected' : ''; 
                                                ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Placeholder (optional)</label>
                                        <input type="text" name="placeholders[]" class="form-control" 
                                               placeholder="Placeholder text" value="<?php echo htmlspecialchars($field['placeholder']); ?>">
                                    </div>
                                    
                                    <div class="field-options" style="<?php 
                                        echo in_array($field['field_type'], ['radio', 'checkbox', 'dropdown']) ? 'display: block;' : 'display: none;'; 
                                    ?>">
                                        <label>Options (comma-separated)</label>
                                        <input type="text" name="options[]" class="form-control" 
                                               value="<?php echo htmlspecialchars(implode(',', json_decode($field['field_options'], true) ?? [])); ?>">
                                        <small>Separate options with commas (e.g., Option 1, Option 2, Option 3)</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="required[]" <?php 
                                                echo $field['is_required'] ? 'checked' : ''; 
                                            ?>>
                                            Required question
                                        </label>
                                    </div>
                                    
                                    <button type="button" class="remove-field btn btn-danger">
                                        <i class="fas fa-trash"></i> Remove Question
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="survey-field">
                                <div class="form-group">
                                    <label for="question-1">Question 1</label>
                                    <input type="text" name="questions[]" id="question-1" class="form-control" 
                                           placeholder="Enter your question" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Field Type</label>
                                    <select name="field_types[]" class="form-control field-type-selector" required>
                                        <?php foreach ($config['fieldTypes'] as $type => $label): ?>
                                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Placeholder (optional)</label>
                                    <input type="text" name="placeholders[]" class="form-control" 
                                           placeholder="Placeholder text">
                                </div>
                                
                                <div class="field-options" style="display: none;">
                                    <label>Options (comma-separated)</label>
                                    <input type="text" name="options[]" class="form-control" 
                                           placeholder="Option1, Option2, Option3">
                                    <small>Separate options with commas (e.g., Option 1, Option 2, Option 3)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="required[]">
                                        Required question
                                    </label>
                                </div>
                                
                                <button type="button" class="remove-field btn btn-danger">
                                    <i class="fas fa-trash"></i> Remove Question
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" id="add-field" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </div>

                <div class="form-section">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Survey
                        </button>
                        <a href="surveys.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> View All Surveys
                        </a>
                        <?php if ($survey_id): ?>
                            <a href="survey_preview.php?id=<?php echo $survey_id; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Preview Survey
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add new question field
        document.getElementById('add-field').addEventListener('click', function() {
            const fieldCount = document.querySelectorAll('.survey-field').length + 1;
            const fieldHTML = `
                <div class="survey-field">
                    <div class="form-group">
                        <label for="question-${fieldCount}">Question ${fieldCount}</label>
                        <input type="text" name="questions[]" id="question-${fieldCount}" 
                               class="form-control" placeholder="Enter your question" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Field Type</label>
                        <select name="field_types[]" class="form-control field-type-selector" required>
                            <?php foreach ($config['fieldTypes'] as $type => $label): ?>
                                <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Placeholder (optional)</label>
                        <input type="text" name="placeholders[]" class="form-control" 
                               placeholder="Placeholder text">
                    </div>
                    
                    <div class="field-options" style="display: none;">
                        <label>Options (comma-separated)</label>
                        <input type="text" name="options[]" class="form-control" 
                               placeholder="Option1, Option2, Option3">
                        <small>Separate options with commas (e.g., Option 1, Option 2, Option 3)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="required[]">
                            Required question
                        </label>
                    </div>
                    
                    <button type="button" class="remove-field btn btn-danger">
                        <i class="fas fa-trash"></i> Remove Question
                    </button>
                </div>`;
            
            document.getElementById('survey-fields').insertAdjacentHTML('beforeend', fieldHTML);
        });

        // Remove question field
        document.getElementById('survey-fields').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-field') || e.target.closest('.remove-field')) {
                e.target.closest('.survey-field').remove();
                // Renumber remaining questions
                document.querySelectorAll('.survey-field').forEach((field, index) => {
                    field.querySelector('label').textContent = `Question ${index + 1}`;
                });
            }
        });

        // Show/hide options based on field type
        document.getElementById('survey-fields').addEventListener('change', function(e) {
            if (e.target.classList.contains('field-type-selector')) {
                const optionsDiv = e.target.closest('.survey-field').querySelector('.field-options');
                if (['radio', 'checkbox', 'dropdown'].includes(e.target.value)) {
                    optionsDiv.style.display = 'block';
                } else {
                    optionsDiv.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>