<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$survey_id = $_GET['id'] ?? 0;

// Validate survey access and get survey details
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.is_anonymous,
               sf.id AS field_id, sf.field_type, sf.field_label, 
               sf.field_options, sf.is_required, sf.display_order
        FROM surveys s
        JOIN survey_fields sf ON s.id = sf.survey_id
        LEFT JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE s.id = ? 
          AND (s.is_public = 1 OR sr.role_id = ?)
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
        ORDER BY sf.display_order
    ");
    $stmt->execute([$survey_id, $_SESSION['role_id'] ?? null]);
    $survey_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($survey_data)) {
        $_SESSION['error'] = "Survey not found or not available for your role.";
        header("Location: survey.php");
        exit();
    }
    
    // Check if user has already responded (only for non-anonymous surveys)
    if (!$survey_data[0]['is_anonymous']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM survey_responses 
            WHERE survey_id = ? AND (user_id = ? OR is_public = 1)
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

        // Collect all answers for JSON storage
        $answers = [];
        foreach ($survey_data as $question) {
            $field_id = $question['field_id'];
            $value = $_POST['field_'.$field_id] ?? null;

            // Validate required fields
            if ($question['is_required'] && (empty($value) && $value !== "0")) {
                throw new Exception("Required question '{$question['field_label']}' was not answered");
            }

            $answers[$field_id] = is_array($value) ? $value : (string)$value;
        }

        // Insert survey response with JSON answers
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
        $response_id = $pdo->lastInsertId();

        // Fix: Insert survey_id into response_data for FK constraint
        foreach ($survey_data as $question) {
            $field_id = $question['field_id'];
            $value = $_POST['field_'.$field_id] ?? null;
            if (!empty($value) || $value === "0") {
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $val) {
                    if ($val !== "" && $val !== null) {
                        $stmt = $pdo->prepare("
                            INSERT INTO response_data 
                            (response_id, survey_id, field_id, field_value) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$response_id, $survey_id, $field_id, $val]);
                    }
                }
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Thank you for completing the survey!";
        header("Location: dashboard.php");
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
    'questions' => []
];

foreach ($survey_data as $row) {
    $survey['questions'][] = [
        'id' => $row['field_id'],
        'type' => $row['field_type'],
        'label' => $row['field_label'],
        'options' => $row['field_options'] ? json_decode($row['field_options']) : [],
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
        .main-content-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 0 20px;
        }
        .erpnext-btn {
            background: #007bfc;
            color: #fff;
            border: 1px solid #007bfc;
            border-radius: 4px;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.2s;
            cursor: pointer;
        }
        .erpnext-btn:hover {
            background: #0056b3;
        }
        .erpnext-input, .erpnext-textarea, .form-control {
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
            background: #f5f7fa;
            color: #36414c;
        }
        .erpnext-input:focus, .erpnext-textarea:focus, .form-control:focus {
            outline: none;
            border-color: #007bfc;
            background: #fff;
        }
        .erpnext-label, .form-label {
            font-weight: 500;
            color: #36414c;
            margin-bottom: 4px;
            display: block;
        }
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="survey-container">
            <h1 class="survey-title" style="color:#007bff;">
                <i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($survey['title']) ?>
            </h1>
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
                        <label class="form-label">
                            <?= htmlspecialchars($question['label']) ?>
                            <?php if ($question['required']): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php switch ($question['type']):
                            case 'text': ?>
                                <input type="text" 
                                       name="field_<?= $question['id'] ?>" 
                                       class="erpnext-input"
                                       <?= $question['required'] ? 'required' : '' ?>>
                                <?php break; 
                                
                            case 'textarea': ?>
                                <textarea name="field_<?= $question['id'] ?>" 
                                          class="erpnext-textarea"
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
                                        class="erpnext-input"
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
                                       class="erpnext-input"
                                       <?= $question['required'] ? 'required' : '' ?>>
                                <?php break; 
                                
                            case 'date': ?>
                                <input type="date" 
                                       name="field_<?= $question['id'] ?>" 
                                       class="erpnext-input"
                                       <?= $question['required'] ? 'required' : '' ?>>
                                <?php break; 
                                
                            case 'rating': ?>
                                <select name="field_<?= $question['id'] ?>" 
                                        class="erpnext-input"
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
                    <button type="submit" class="erpnext-btn">
                        <i class="fas fa-paper-plane"></i> Submit Survey
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="../includes/activity-tracker.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>