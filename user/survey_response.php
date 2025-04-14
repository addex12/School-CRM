<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Validate survey access and get survey details
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.is_anonymous, s.allow_multiple,
               sf.id AS field_id, sf.field_type, sf.field_label, sf.field_name,
               sf.field_options, sf.is_required, sf.display_order
        FROM surveys s
        JOIN survey_fields sf ON s.id = sf.survey_id
        JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE s.id = ? 
          AND sr.role_id = ?
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
        ORDER BY sf.display_order
    ");
    $stmt->execute([$survey_id, $_SESSION['role_id']]);
    $survey_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($survey_data)) {
        $_SESSION['error'] = "Survey not found or not available for your role.";
        header("Location: survey.php");
        exit();
    }
    
    // Check if user has already responded (only for non-anonymous surveys that don't allow multiple responses)
    if (!$survey_data[0]['is_anonymous'] && !$survey_data[0]['allow_multiple']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM survey_responses 
            WHERE survey_id = ? AND user_id = ?
        ");
        $stmt->execute([$survey_id, $_SESSION['user_id']]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "You have already completed this survey.";
            header("Location: survey.php");
            exit();
        }
    }
} catch (PDOException $e) {
    error_log("Error validating survey access: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while loading the survey.";
    header("Location: survey.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Prepare all answers in JSON format
        $answers = [];
        foreach ($survey_data as $question) {
            $field_name = $question['field_name'];
            $value = $_POST['field_'.$question['field_id']] ?? null;
            
            // Validate required fields
            if ($question['is_required'] && empty($value)) {
                throw new Exception("Required question '{$question['field_label']}' was not answered");
            }
            
            // Handle different field types
            if (!empty($value)) {
                if (is_array($value)) {
                    $answers[$field_name] = array_map('htmlspecialchars', $value);
                } else {
                    $answers[$field_name] = htmlspecialchars($value);
                }
            } else {
                $answers[$field_name] = null;
            }
        }
        
        // Insert survey response with all answers as JSON
        $stmt = $pdo->prepare("
            INSERT INTO survey_responses 
            (survey_id, user_id, submitted_at, answers) 
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $survey_id, 
            $_SESSION['user_id'],
            json_encode($answers, JSON_UNESCAPED_UNICODE)
        ]);
        
        $pdo->commit();
        $_SESSION['success'] = "Thank you for completing the survey!";
        header("Location: survey.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving survey response: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }
}

// Prepare survey data for display
$survey = [
    'id' => $survey_data[0]['id'],
    'title' => $survey_data[0]['title'],
    'description' => $survey_data[0]['description'],
    'is_anonymous' => $survey_data[0]['is_anonymous'],
    'allow_multiple' => $survey_data[0]['allow_multiple'],
    'questions' => []
];

foreach ($survey_data as $row) {
    $survey['questions'][] = [
        'id' => $row['field_id'],
        'name' => $row['field_name'],
        'type' => $row['field_type'],
        'label' => $row['field_label'],
        'options' => $row['field_options'] ? json_decode($row['field_options'], true) : [],
        'required' => $row['is_required']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey['title']) ?> - Survey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .survey-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .survey-title {
            margin-top: 0;
            color: #2c3e50;
        }
        .survey-description {
            color: #666;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .question-group {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .question-label {
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            color: #2c3e50;
        }
        .required {
            color: #dc3545;
            margin-left: 3px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .form-check {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .form-check-input {
            margin-right: 10px;
        }
        .form-check-label {
            cursor: pointer;
        }
        .options-list {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
        }
        .anonymous-notice {
            background: #e7f5fe;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
            display: flex;
            align-items: center;
        }
        .anonymous-notice i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-submit:hover {
            background: #218838;
        }
        .btn-submit i {
            margin-right: 8px;
        }
        .progress-container {
            margin-bottom: 20px;
        }
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #3498db;
            width: 0%;
            transition: width 0.5s;
        }
        .progress-text {
            text-align: right;
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .file-upload {
            display: flex;
            flex-direction: column;
        }
        .file-upload-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
        .rating-options {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }
        .rating-option {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .rating-option input {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="survey-container">
        <h1 class="survey-title"><?= htmlspecialchars($survey['title']) ?></h1>
        <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="survey-form" enctype="multipart/form-data">
            <?php if ($survey['is_anonymous']): ?>
                <div class="anonymous-notice">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        <strong>Anonymous Survey</strong>
                        <p>Your responses will not be linked to your identity.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">0% complete</div>
            </div>
            
            <?php foreach ($survey['questions'] as $question): ?>
                <div class="question-group" id="question-<?= $question['id'] ?>">
                    <label class="question-label">
                        <?= htmlspecialchars($question['label']) ?>
                        <?php if ($question['required']): ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php switch ($question['type']):
                        case 'text': ?>
                            <input type="text" 
                                   name="field_<?= $question['id'] ?>" 
                                   class="form-control"
                                   data-question-id="<?= $question['id'] ?>"
                                   <?= $question['required'] ? 'required' : '' ?>>
                            <?php break; 
                            
                        case 'textarea': ?>
                            <textarea name="field_<?= $question['id'] ?>" 
                                      class="form-control"
                                      data-question-id="<?= $question['id'] ?>"
                                      <?= $question['required'] ? 'required' : '' ?>></textarea>
                            <?php break; 
                            
                        case 'radio': ?>
                            <ul class="options-list">
                                <?php foreach ($question['options'] as $option): ?>
                                    <li class="form-check">
                                        <input type="radio" 
                                               name="field_<?= $question['id'] ?>" 
                                               value="<?= htmlspecialchars($option) ?>" 
                                               id="field_<?= $question['id'] ?>_<?= md5($option) ?>"
                                               class="form-check-input"
                                               data-question-id="<?= $question['id'] ?>"
                                               <?= $question['required'] ? 'required' : '' ?>>
                                        <label class="form-check-label" 
                                               for="field_<?= $question['id'] ?>_<?= md5($option) ?>">
                                            <?= htmlspecialchars($option) ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php break; 
                            
                        case 'checkbox': ?>
                            <ul class="options-list">
                                <?php foreach ($question['options'] as $option): ?>
                                    <li class="form-check">
                                        <input type="checkbox" 
                                               name="field_<?= $question['id'] ?>[]" 
                                               value="<?= htmlspecialchars($option) ?>" 
                                               id="field_<?= $question['id'] ?>_<?= md5($option) ?>"
                                               class="form-check-input"
                                               data-question-id="<?= $question['id'] ?>">
                                        <label class="form-check-label" 
                                               for="field_<?= $question['id'] ?>_<?= md5($option) ?>">
                                            <?= htmlspecialchars($option) ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php break; 
                            
                        case 'select': ?>
                            <select name="field_<?= $question['id'] ?>" 
                                    class="form-control"
                                    data-question-id="<?= $question['id'] ?>"
                                    <?= $question['required'] ? 'required' : '' ?>>
                                <option value="">-- Select an option --</option>
                                <?php foreach ($question['options'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>">
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php break; 
                            
                        case 'number': ?>
                            <input type="number" 
                                   name="field_<?= $question['id'] ?>" 
                                   class="form-control"
                                   data-question-id="<?= $question['id'] ?>"
                                   <?= $question['required'] ? 'required' : '' ?>>
                            <?php break; 
                            
                        case 'date': ?>
                            <input type="date" 
                                   name="field_<?= $question['id'] ?>" 
                                   class="form-control"
                                   data-question-id="<?= $question['id'] ?>"
                                   <?= $question['required'] ? 'required' : '' ?>>
                            <?php break; 
                            
                        case 'rating': ?>
                            <div class="rating-options">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="rating-option">
                                        <input type="radio" 
                                               name="field_<?= $question['id'] ?>" 
                                               value="<?= $i ?>" 
                                               id="field_<?= $question['id'] ?>_<?= $i ?>"
                                               class="form-check-input"
                                               data-question-id="<?= $question['id'] ?>"
                                               <?= $question['required'] ? 'required' : '' ?>>
                                        <label for="field_<?= $question['id'] ?>_<?= $i ?>"><?= $i ?></label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <?php break;
                            
                        case 'file': ?>
                            <div class="file-upload">
                                <input type="file" 
                                       name="field_<?= $question['id'] ?>" 
                                       class="form-control"
                                       data-question-id="<?= $question['id'] ?>"
                                       <?= $question['required'] ? 'required' : '' ?>>
                                <img src="" class="file-upload-preview" id="preview-<?= $question['id'] ?>">
                            </div>
                            <?php break;
                            
                    endswitch; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-group text-center">
                <button type="submit" class="btn-submit">
                    <i class="bi bi-send"></i> Submit Survey
                </button>
            </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Progress tracking
            const form = document.getElementById('survey-form');
            const questions = document.querySelectorAll('.question-group');
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            
            // Calculate progress
            function updateProgress() {
                let answered = 0;
                questions.forEach(question => {
                    const questionId = question.id.replace('question-', '');
                    const inputs = form.querySelectorAll(`[data-question-id="${questionId}"]`);
                    
                    let isAnswered = false;
                    inputs.forEach(input => {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            if (input.checked) isAnswered = true;
                        } else if (input.type === 'file') {
                            if (input.files.length > 0) isAnswered = true;
                        } else {
                            if (input.value.trim() !== '') isAnswered = true;
                        }
                    });
                    
                    if (isAnswered) answered++;
                });
                
                const progress = Math.round((answered / questions.length) * 100);
                progressFill.style.width = `${progress}%`;
                progressText.textContent = `${progress}% complete`;
            }
            
            // File preview
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', function() {
                    const previewId = `preview-${this.getAttribute('data-question-id')}`;
                    const preview = document.getElementById(previewId);
                    
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                        updateProgress();
                    }
                });
            });
            
            // Update progress on any input change
            form.addEventListener('input', updateProgress);
            form.addEventListener('change', updateProgress);
            
            // Initialize progress
            updateProgress();
            
            // Form submission handling
            form.addEventListener('submit', function(e) {
                // Validate required fields
                let isValid = true;
                questions.forEach(question => {
                    const questionId = question.id.replace('question-', '');
                    const inputs = form.querySelectorAll(`[data-question-id="${questionId}"]`);
                    const isRequired = inputs[0].hasAttribute('required');
                    
                    if (isRequired) {
                        let isAnswered = false;
                        inputs.forEach(input => {
                            if (input.type === 'checkbox' || input.type === 'radio') {
                                if (input.checked) isAnswered = true;
                            } else if (input.type === 'file') {
                                if (input.files.length > 0) isAnswered = true;
                            } else {
                                if (input.value.trim() !== '') isAnswered = true;
                            }
                        });
                        
                        if (!isAnswered) {
                            isValid = false;
                            question.style.borderLeftColor = '#dc3545';
                        } else {
                            question.style.borderLeftColor = '#3498db';
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please answer all required questions marked with *.');
                }
            });
        });
    </script>
</body>
</html>