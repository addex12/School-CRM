<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info with role validation
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM surveys s
    WHERE s.id = ? 
    AND s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
    AND JSON_CONTAINS(s.target_roles, JSON_QUOTE(?))
");
$stmt->execute([$survey_id, $_SESSION['role_id']]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php?error=survey_not_found");
    exit();
}
$stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_role_id = $stmt->fetchColumn();

// Check against target_roles
$allowed_roles = json_decode($survey['target_roles'], true);
if (!in_array($user_role_id, $allowed_roles)) {
    header("Location: dashboard.php?error=not_authorized");
    exit();
}
// Check if user has already completed this survey
$stmt = $pdo->prepare("SELECT id FROM survey_responses WHERE survey_id = ? AND user_id = ?");
$stmt->execute([$survey_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    header("Location: dashboard.php?error=already_completed");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response_data = [];
    
    foreach ($fields as $field) {
        $field_name = $field['field_name'];
        $field_value = $_POST[$field_name] ?? null;
        
        // Validate required fields
        if ($field['is_required'] && empty($field_value)) {
            $errors[$field_name] = "This field is required";
            continue;
        }

        // Field-type specific validation
        $validation_rules = $field['validation_rules'] ? json_decode($field['validation_rules'], true) : null;
        if ($validation_rules) {
            // Min validation
            if (isset($validation_rules['min']) && $field_value) {
                if ($field['field_type'] === 'number' && $field_value < $validation_rules['min']) {
                    $errors[$field_name] = "Value must be at least {$validation_rules['min']}";
                } elseif (in_array($field['field_type'], ['text', 'textarea']) && strlen($field_value) < $validation_rules['min']) {
                    $errors[$field_name] = "Must be at least {$validation_rules['min']} characters";
                }
            }
            
            // Max validation
            if (isset($validation_rules['max']) && $field_value) {
                if ($field['field_type'] === 'number' && $field_value > $validation_rules['max']) {
                    $errors[$field_name] = "Value must be at most {$validation_rules['max']}";
                } elseif (in_array($field['field_type'], ['text', 'textarea']) && strlen($field_value) > $validation_rules['max']) {
                    $errors[$field_name] = "Must be at most {$validation_rules['max']} characters";
                }
            }
        }

        // Handle file uploads
        if ($field['field_type'] === 'file' && isset($_FILES[$field_name])) {
            $file = $_FILES[$field_name];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $upload_dir = "../uploads/survey_{$survey_id}";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = "user_{$_SESSION['user_id']}_" . uniqid() . ".$ext";
                if (move_uploaded_file($file['tmp_name'], "$upload_dir/$filename")) {
                    $field_value = $filename;
                } else {
                    $errors[$field_name] = "File upload failed";
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[$field_name] = "File upload error";
            }
        }

        // Handle checkboxes
        if ($field['field_type'] === 'checkbox' && isset($_POST[$field_name])) {
            $field_value = implode(', ', $_POST[$field_name]);
        }

        $response_data[] = [
            'field_id' => $field['id'],
            'field_value' => $field_value
        ];
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
                $stmt = $pdo->prepare("INSERT INTO response_data (response_id, field_id, field_value) VALUES (?, ?, ?)");
                $stmt->execute([$response_id, $data['field_id'], $data['field_value']]);
            }
            
            $pdo->commit();
            header("Location: thank_you.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error saving response: " . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="survey-container">
    <h1><?= htmlspecialchars($survey['title']) ?></h1>
    <p><?= htmlspecialchars($survey['description']) ?></p>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= $error ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php foreach ($fields as $field): ?>
            <div class="field-group">
                <label>
                    <?= htmlspecialchars($field['field_label']) ?>
                    <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                </label>
                
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
                
                <?php elseif ($field['field_type'] === 'rating'): ?>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-value="<?= $i ?>">★</span>
                        <?php endfor; ?>
                        <input type="hidden" name="<?= $field['field_name'] ?>" 
                               value="<?= $_POST[$field['field_name']] ?? '' ?>">
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors[$field['field_name']])): ?>
                    <div class="error"><?= $errors[$field['field_name']] ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Submit Survey</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Rating stars functionality
    document.querySelectorAll('.rating-stars').forEach(container => {
        const stars = container.querySelectorAll('.star');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = star.dataset.value;
                hiddenInput.value = value;
                stars.forEach((s, index) => {
                    s.style.color = index < value ? '#ffd700' : '#ccc';
                });
            });
        });
    });
</script>
</body>
</html>