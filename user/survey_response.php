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
            $field_name = $question['field_name'] ?: 'field_'.$question['field_id'];
            $value = $_POST['field_'.$question['field_id']] ?? null;
            
            // Validate required fields
            if ($question['is_required'] && empty($value)) {
                throw new Exception("Required question '{$question['field_label']}' was not answered");
            }
            
            // Handle different field types
            if (!empty($value)) {
                if (is_array($value)) {
                    $answers[$field_name] = array_map('trim', $value);
                } else {
                    $answers[$field_name] = trim($value);
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
        'name' => $row['field_name'] ?: 'field_'.$row['field_id'],
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
    <style>
        .survey-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .survey-title {
            margin-top: 0;
            color: #333;
        }
        .survey-description {
            color: #666;
            margin-bottom: 20px;
        }
        .question-group {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .question-label {
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }
        .required {
            color: #dc3545;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea.form-control {
            min-height: 100px;
        }
        .form-check {
            margin-bottom: 8px;
        }
        .form-check-label {
            margin-left: 5px;
        }
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover {
            background: #218838;
        }
        .options-list {
            list-style: none;
            padding-left: 0;
        }
        .anonymous-notice {
            background: #e7f5fe;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="survey-container">
        <h1 class="survey-title"><?= htmlspecialchars($survey['title']) ?></h1>
        <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php if ($survey['is_anonymous']): ?>
                <div class="anonymous-notice">
                    <i class="fas fa-user-secret"></i> 
                    This survey is anonymous. Your responses will not be linked to your identity.
                </div>
            <?php endif; ?>
            
            <?php foreach ($survey['questions'] as $question): ?>
                <div class="question-group">
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
                                   <?= $question['required'] ? 'required' : '' ?>>
                            <?php break; 
                            
                        case 'textarea': ?>
                            <textarea name="field_<?= $question['id'] ?>" 
                                      class="form-control"
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
                                               class="form-check-input">
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
                                   <?= $question['required'] ? 'required' : '' ?>>
                            <?php break; 
                            
                        case 'date': ?>
                            <input type="date" 
                                   name="field_<?= $question['id'] ?>" 
                                   class="form-control"
                                   <?= $question['required'] ? 'required' : '' ?>>
                            <?php break; 
                            
                        case 'rating': ?>
                            <select name="field_<?= $question['id'] ?>" 
                                    class="form-control"
                                    <?= $question['required'] ? 'required' : '' ?>>
                                <option value="">-- Select rating --</option>
                                <option value="1">1 - Poor</option>
                                <option value="2">2 - Fair</option>
                                <option value="3">3 - Good</option>
                                <option value="4">4 - Very Good</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                            <?php break; 
                            
                    endswitch; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-group text-center">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Survey
                </button>
            </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>