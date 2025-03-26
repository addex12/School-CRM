<?php
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Fetch survey info
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

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $response_data = [];

    foreach ($fields as $field) {
        $field_name = $field['field_name'];
        $field_value = $_POST[$field_name] ?? null;

        // Validate required fields
        if ($field['is_required'] && empty($field_value)) {
            $errors[$field_name] = "This field is required.";
            continue;
        }

        // Validate field-specific rules
        $validation_rules = json_decode($field['validation_rules'], true);
        if ($validation_rules) {
            // Min/Max validation
            if (isset($validation_rules['min']) && $field_value) {
                if ($field['field_type'] === 'number' && $field_value < $validation_rules['min']) {
                    $errors[$field_name] = "Value must be at least {$validation_rules['min']}.";
                } elseif (in_array($field['field_type'], ['text', 'textarea']) && strlen($field_value) < $validation_rules['min']) {
                    $errors[$field_name] = "Must be at least {$validation_rules['min']} characters.";
                }
            }
            if (isset($validation_rules['max']) && $field_value) {
                if ($field['field_type'] === 'number' && $field_value > $validation_rules['max']) {
                    $errors[$field_name] = "Value must be at most {$validation_rules['max']}.";
                } elseif (in_array($field['field_type'], ['text', 'textarea']) && strlen($field_value) > $validation_rules['max']) {
                    $errors[$field_name] = "Must be at most {$validation_rules['max']} characters.";
                }
            }

            // Regex validation
            if (isset($validation_rules['regex']) && $field_value) {
                if (!preg_match("/{$validation_rules['regex']}/", $field_value)) {
                    $errors[$field_name] = "Invalid format.";
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
                $filepath = "$upload_dir/$filename";
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $field_value = $filename;
                } else {
                    $errors[$field_name] = "Failed to upload file.";
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[$field_name] = "File upload error.";
            }
        }

        // Handle checkbox fields
        if ($field['field_type'] === 'checkbox' && isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
            $field_value = implode(', ', $_POST[$field_name]);
        }

        // Validate radio/select options
        if (in_array($field['field_type'], ['radio', 'select']) && $field_value) {
            $options = json_decode($field['field_options'], true);
            if (!in_array($field_value, $options)) {
                $errors[$field_name] = "Invalid selection.";
            }
        }

        // Validate rating fields
        if ($field['field_type'] === 'rating' && $field_value) {
            $rating = intval($field_value);
            if ($rating < 1 || $rating > 5) {
                $errors[$field_name] = "Invalid rating value.";
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

            // Save survey response
            $stmt = $pdo->prepare("INSERT INTO survey_responses (survey_id, user_id) VALUES (?, ?)");
            $stmt->execute([$survey_id, $_SESSION['user_id']]);
            $response_id = $pdo->lastInsertId();

            // Save response data
            foreach ($response_data as $data) {
                $stmt = $pdo->prepare("INSERT INTO response_data (response_id, field_id, field_value) VALUES (?, ?, ?)");
                $stmt->execute([$response_id, $data['field_id'], $data['field_value']]);
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
        .field-error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
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

        <div class="survey-content">
            <div class="survey-header">
                <h2 class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></h2>
                <p class="survey-description"><?php echo htmlspecialchars($survey['description']); ?></p>
            </div>

            <?php if (isset($errors['system'])): ?>
                <div class="error-message"><?php echo $errors['system']; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php foreach ($fields as $field): ?>
                    <div class="form-group">
                        <label for="<?php echo $field['field_name']; ?>">
                            <?php echo htmlspecialchars($field['field_label']); ?>
                            <?php if ($field['is_required']): ?><span class="required">*</span><?php endif; ?>
                        </label>
                        <?php if ($field['field_type'] === 'text'): ?>
                            <input type="text" id="<?php echo $field['field_name']; ?>" name="<?php echo $field['field_name']; ?>" value="<?php echo htmlspecialchars($_POST[$field['field_name']] ?? ''); ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <?php elseif ($field['field_type'] === 'textarea'): ?>
                            <textarea id="<?php echo $field['field_name']; ?>" name="<?php echo $field['field_name']; ?>" rows="4" <?php echo $field['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($_POST[$field['field_name']] ?? ''); ?></textarea>
                        <?php elseif ($field['field_type'] === 'radio'): ?>
                            <div>
                                <?php foreach (json_decode($field['field_options'], true) as $option): ?>
                                    <label>
                                        <input type="radio" name="<?php echo $field['field_name']; ?>" value="<?php echo htmlspecialchars($option); ?>" <?php echo ($_POST[$field['field_name']] ?? '') === $option ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($field['field_type'] === 'checkbox'): ?>
                            <div>
                                <?php foreach (json_decode($field['field_options'], true) as $option): ?>
                                    <label>
                                        <input type="checkbox" name="<?php echo $field['field_name']; ?>[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $_POST[$field['field_name']] ?? []) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($field['field_type'] === 'select'): ?>
                            <select id="<?php echo $field['field_name']; ?>" name="<?php echo $field['field_name']; ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                <option value="">Select an option</option>
                                <?php foreach (json_decode($field['field_options'], true) as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($_POST[$field['field_name']] ?? '') === $option ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['field_type'] === 'file'): ?>
                            <input type="file" id="<?php echo $field['field_name']; ?>" name="<?php echo $field['field_name']; ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <?php endif; ?>
                        <?php if (isset($errors[$field['field_name']])): ?>
                            <div class="field-error"><?php echo $errors[$field['field_name']]; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Survey</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>