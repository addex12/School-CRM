<?php
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info
$stmt = $pdo->prepare("
    SELECT s.*, GROUP_CONCAT(f.field_name) as field_names 
    FROM surveys s
    LEFT JOIN survey_fields f ON s.id = f.survey_id
    WHERE s.id = ? AND s.is_active = TRUE 
    AND (s.starts_at <= NOW() AND s.ends_at >= NOW())
    GROUP BY s.id
");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php?error=survey_not_found");
    exit();
}

// Check if user has permission to take this survey
$allowed_roles = json_decode($survey['target_roles'], true);
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: dashboard.php?error=not_authorized");
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

<?php include 'includes/header.php'; ?>

<div class="container">
    <header>
        <h1>Survey: <?php echo htmlspecialchars($survey['title']); ?></h1>
        <nav>
            <a href="dashboard.php">Back to Dashboard</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    
    <div class="survey-content">
        <div class="survey-header">
            <h2 class="survey-title"><?= htmlspecialchars($survey['title']) ?></h2>
            <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
            <div class="survey-meta">
                <p>Available from <?= date('M j, Y g:i A', strtotime($survey['starts_at'])) ?> to <?= date('M j, Y g:i A', strtotime($survey['ends_at'])) ?></p>
            </div>
        </div>
        <?php if (isset($errors['system'])): ?>
            <div class="error-message"><?= $errors['system'] ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?php foreach ($fields as $field): ?>
                <div class="form-field">
                    <!-- Render input fields dynamically -->
                    <?php if ($field['field_type'] === 'text'): ?>
                        <input type="text" name="<?= $field['field_name'] ?>" class="form-control"
                               value="<?= htmlspecialchars($_POST[$field['field_name']] ?? '') ?>">
                    <?php elseif ($field['field_type'] === 'textarea'): ?>
                        <textarea name="<?= $field['field_name'] ?>" class="form-control" rows="4"><?= 
                            htmlspecialchars($_POST[$field['field_name']] ?? '') ?></textarea>
                    <?php elseif ($field['field_type'] === 'radio'): ?>
                        <?php foreach (json_decode($field['field_options']) as $option): ?>
                            <div class="form-check">
                                <input type="radio" name="<?= $field['field_name'] ?>" 
                                       value="<?= htmlspecialchars($option) ?>" class="form-check-input">
                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                        <?php foreach (json_decode($field['field_options']) as $option): ?>
                            <div class="form-check">
                                <input type="checkbox" name="<?= $field['field_name'] ?>[]" 
                                       value="<?= htmlspecialchars($option) ?>" class="form-check-input">
                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($field['field_type'] === 'file'): ?>
                        <input type="file" name="<?= $field['field_name'] ?>" class="form-control">
                    <?php endif; ?>
                    <?php if (isset($errors[$field['field_name']])): ?>
                        <div class="error"><?= $errors[$field['field_name']] ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Submit Response</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

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
</script>
</body>
</html>