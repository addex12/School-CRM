<?php
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM surveys s
    WHERE s.id = ? 
    AND s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
    AND JSON_CONTAINS(s.target_roles, JSON_QUOTE(?))
");
$stmt->execute([$survey_id, $_SESSION['role']]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php?error=survey_not_found");
    exit();
}

// Check if user has already completed this survey
$stmt = $pdo->prepare("SELECT id FROM survey_responses WHERE survey_id = ? AND user_id = ?");
$stmt->execute([$survey_id, $_SESSION['user_id']]);
$completed = $stmt->fetch();

if ($completed) {
    header("Location: dashboard.php?error=already_completed");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $response_data = [];
    
    // Validate all required fields
    foreach ($fields as $field) {
        $field_name = $field['field_name'];
        $field_value = $_POST[$field_name] ?? null;
        
        // Check required fields
        if ($field['is_required'] && empty($field_value)) {
            $errors[$field_name] = "This field is required";
            continue;
        }
        
        // Validate based on field type
        $validation_rules = json_decode($field['validation_rules'], true);
        
        if ($validation_rules) {
            // Min length/value validation
            if (isset($validation_rules['min']) && $field_value) {
                if ($field['field_type'] === 'number' && $field_value < $validation_rules['min']) {
                    $errors[$field_name] = "Value must be at least {$validation_rules['min']}";
                } elseif (in_array($field['field_type'], ['text', 'textarea']) && strlen($field_value) < $validation_rules['min']) {
                    $errors[$field_name] = "Must be at least {$validation_rules['min']} characters";
                }
            }
            
            // Max length/value validation
            if (isset($validation_rules['max']) && $field_value) {
                if ($field['field_type'] === 'number' && $field_value > $validation_rules['max']) {
                    $errors[$field_name] = "Value must be at most {$validation_rules['max']}";
                } elseif (in_array($field['field_type'], ['text', 'textarea']) && strlen($field_value) > $validation_rules['max']) {
                    $errors[$field_name] = "Must be at most {$validation_rules['max']} characters";
                }
            }
            
            // Regex validation
            if (isset($validation_rules['regex']) && $field_value) {
                if (!preg_match("/{$validation_rules['regex']}/", $field_value)) {
                    $errors[$field_name] = "Invalid format";
                }
            }
        }
        
        // Handle file uploads
        if ($field['field_type'] === 'file' && isset($_FILES[$field_name])) {
            $file = $_FILES[$field_name];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Create uploads directory if it doesn't exist
                $upload_dir = "../uploads/survey_{$survey_id}";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = "user_{$_SESSION['user_id']}_" . uniqid() . ".$ext";
                $filepath = "$upload_dir/$filename";
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $field_value = $filename;
                } else {
                    $errors[$field_name] = "Failed to upload file";
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[$field_name] = "File upload error";
            }
        }
        
        // For checkbox fields, handle array values
        if ($field['field_type'] === 'checkbox' && isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
            $field_value = implode(', ', $_POST[$field_name]);
        }
        
        // For radio/select fields, ensure value is in options
        if (in_array($field['field_type'], ['radio', 'select']) && $field_value) {
            $options = json_decode($field['field_options'], true);
            if (!in_array($field_value, $options)) {
                $errors[$field_name] = "Invalid selection";
            }
        }
        
        // For rating fields, validate range
        if ($field['field_type'] === 'rating' && $field_value) {
            $rating = intval($field_value);
            if ($rating < 1 || $rating > 5) {
                $errors[$field_name] = "Invalid rating value";
            }
        }
        
        if (!isset($errors[$field_name])) {
            $response_data[] = [
                'field_id' => $field['id'],
                'field_value' => $field_value
            ];
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create survey response
            $stmt = $pdo->prepare("INSERT INTO survey_responses (survey_id, user_id) VALUES (?, ?)");
            $stmt->execute([$survey_id, $_SESSION['user_id']]);
            $response_id = $pdo->lastInsertId();
            
            // Save response data
            foreach ($response_data as $data) {
                if ($data['field_value'] !== null) {
                    $stmt = $pdo->prepare("INSERT INTO response_data (response_id, field_id, field_value) VALUES (?, ?, ?)");
                    $stmt->execute([$response_id, $data['field_id'], $data['field_value']]);
                }
            }
            
            $pdo->commit();
            
            header("Location: dashboard.php?survey_completed=$survey_id");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['system'] = "An error occurred while saving your response. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($survey['title']); ?> - Survey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .survey-header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .survey-title {
            margin: 0;
            font-size: 24px;
        }
        .survey-description {
            margin: 10px 0 0;
        }
        .survey-meta {
            margin-top: 15px;
            font-size: 14px;
        }
        .field-error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        .file-preview {
            max-width: 200px;
            max-height: 200px;
            display: block;
            margin-top: 10px;
        }
        .progress-bar {
            height: 5px;
            background-color: #3498db;
            margin-bottom: 20px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($survey['title']); ?></h1>
            <nav>
                <a href="dashboard.php">Back to Dashboard</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="progress-bar" style="width: <?php echo count($fields) > 0 ? (100/count($fields)) : 100; ?>%"></div>
        
        <div class="survey-content">
            <div class="survey-header">
                <h2 class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></h2>
                <p class="survey-description"><?php echo htmlspecialchars($survey['description']); ?></p>
                <div class="survey-meta">
                    <p>Deadline: <?php echo date('M j, Y g:i A', strtotime($survey['ends_at'])); ?></p>
                    <?php 
                    $now = new DateTime();
                    $end = new DateTime($survey['ends_at']);
                    $interval = $now->diff($end);
                    ?>
                    <p>Time remaining: <?php echo $interval->format('%a days %h hours'); ?></p>
                </div>
            </div>
            
            <?php if (isset($errors['system'])): ?>
                <div class="error-message"><?php echo $errors['system']; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="survey-form">
                <?php foreach ($fields as $field): ?>
                    <?php
                    $field_name = $field['field_name'];
                    $field_value = $_POST[$field_name] ?? '';
                    $error = $errors[$field_name] ?? '';
                    ?>
                    
                    <div class="form-field <?php echo $error ? 'has-error' : ''; ?>">
                        <?php if ($field['field_type'] === 'text'): ?>
                            <div class="form-group">
                                <label for="<?php echo $field_name; ?>">
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input type="text" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                       value="<?php echo htmlspecialchars($field_value); ?>"
                                       <?php if ($field['is_required']): ?>required<?php endif; ?>>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'textarea'): ?>
                            <div class="form-group">
                                <label for="<?php echo $field_name; ?>">
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <textarea id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                          rows="4" <?php if ($field['is_required']): ?>required<?php endif; ?>><?php 
                                          echo htmlspecialchars($field_value); ?></textarea>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'radio'): ?>
                            <div class="form-group">
                                <label>
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <div class="options">
                                    <?php 
                                    $options = json_decode($field['field_options'], true);
                                    foreach ($options as $option): ?>
                                        <label class="option">
                                            <input type="radio" name="<?php echo $field_name; ?>" 
                                                   value="<?php echo htmlspecialchars($option); ?>"
                                                   <?php if ($field_value === $option): ?>checked<?php endif; ?>
                                                   <?php if ($field['is_required']): ?>required<?php endif; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'checkbox'): ?>
                            <div class="form-group">
                                <label>
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <div class="options">
                                    <?php 
                                    $options = json_decode($field['field_options'], true);
                                    $selected_values = is_array($field_value) ? $field_value : explode(', ', $field_value);
                                    foreach ($options as $option): ?>
                                        <label class="option">
                                            <input type="checkbox" name="<?php echo $field_name; ?>[]" 
                                                   value="<?php echo htmlspecialchars($option); ?>"
                                                   <?php if (in_array($option, $selected_values)): ?>checked<?php endif; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'select'): ?>
                            <div class="form-group">
                                <label for="<?php echo $field_name; ?>">
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <select id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>"
                                        <?php if ($field['is_required']): ?>required<?php endif; ?>>
                                    <option value="">Select an option</option>
                                    <?php 
                                    $options = json_decode($field['field_options'], true);
                                    foreach ($options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>"
                                                <?php if ($field_value === $option): ?>selected<?php endif; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'number'): ?>
                            <div class="form-group">
                                <label for="<?php echo $field_name; ?>">
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input type="number" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                       value="<?php echo htmlspecialchars($field_value); ?>"
                                       <?php if ($field['is_required']): ?>required<?php endif; ?>>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'date'): ?>
                            <div class="form-group">
                                <label for="<?php echo $field_name; ?>">
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input type="date" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                       value="<?php echo htmlspecialchars($field_value); ?>"
                                       <?php if ($field['is_required']): ?>required<?php endif; ?>>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'rating'): ?>
                            <div class="form-group">
                                <label>
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <div class="rating-container">
                                    <input type="hidden" name="<?php echo $field_name; ?>" value="<?php echo htmlspecialchars($field_value); ?>">
                                    <span class="rating-star" data-value="1">★</span>
                                    <span class="rating-star" data-value="2">★</span>
                                    <span class="rating-star" data-value="3">★</span>
                                    <span class="rating-star" data-value="4">★</span>
                                    <span class="rating-star" data-value="5">★</span>
                                    <div class="rating-labels">
                                        <span>1 (Poor)</span>
                                        <span>5 (Excellent)</span>
                                    </div>
                                </div>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        
                        <?php elseif ($field['field_type'] === 'file'): ?>
                            <div class="form-group">
                                <label for="<?php echo $field_name; ?>">
                                    <?php echo htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input type="file" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>"
                                       <?php if ($field['is_required']): ?>required<?php endif; ?>>
                                <?php if ($error): ?><div class="field-error"><?php echo $error; ?></div><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Survey</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Initialize rating fields
        document.querySelectorAll('.rating-container').forEach(container => {
            const stars = container.querySelectorAll('.rating-star');
            const hiddenInput = container.querySelector('input[type="hidden"]');
            
            // Set initial stars if value exists
            if (hiddenInput.value) {
                const value = parseInt(hiddenInput.value);
                stars.forEach((star, i) => {
                    if (i < value) {
                        star.classList.add('active');
                    }
                });
            }
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = parseInt(this.dataset.value);
                    stars.forEach((s, i) => {
                        if (i < value) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                    hiddenInput.value = value;
                });
            });
        });
        
        // Preview image before upload
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(readerEvent) {
                        // Remove existing preview if any
                        const existingPreview = input.nextElementSibling;
                        if (existingPreview && existingPreview.classList.contains('file-preview')) {
                            existingPreview.remove();
                        }
                        
                        // Create new preview
                        const preview = document.createElement('img');
                        preview.src = readerEvent.target.result;
                        preview.className = 'file-preview';
                        input.parentNode.insertBefore(preview, input.nextSibling);
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
        });
        
        // Form progress tracking
        document.getElementById('survey-form').addEventListener('input', function() {
            const fields = document.querySelectorAll('.form-field');
            let completed = 0;
            
            fields.forEach(field => {
                const inputs = field.querySelectorAll('input:not([type="hidden"]), textarea, select');
                let fieldCompleted = false;
                
                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        if (input.checked) fieldCompleted = true;
                    } else if (input.type === 'file') {
                        if (input.files.length > 0) fieldCompleted = true;
                    } else {
                        if (input.value.trim() !== '') fieldCompleted = true;
                    }
                });
                
                if (fieldCompleted) completed++;
            });
            
            const progress = (completed / fields.length) * 100;
            document.querySelector('.progress-bar').style.width = progress + '%';
        });
    </script>
</body>
</html>