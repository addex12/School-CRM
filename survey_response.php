<?php
ob_start(); // Start output buffering

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the database connection file
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Get survey_id from GET parameter and validate
if (!isset($_GET['survey_id']) || !is_numeric($_GET['survey_id'])) {
    die("Invalid or missing survey ID.");
}
$survey_id = (int)$_GET['survey_id'];

// Validate survey access and get survey details
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.is_anonymous,
               sf.id AS field_id, sf.field_type, sf.field_label, 
               sf.field_options, sf.is_required, sf.display_order
        FROM surveys s
        JOIN survey_fields sf ON s.id = sf.survey_id
        WHERE s.id = :survey_id
          AND s.is_public = 1
          AND s.is_active = 1
          AND (s.starts_at IS NULL OR s.starts_at <= NOW())
          AND (s.ends_at IS NULL OR s.ends_at >= NOW())
        ORDER BY sf.display_order
    ");
    $stmt->execute([':survey_id' => $survey_id]);
    $survey_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($survey_data)) {
        // Debugging: Check if the survey exists and is public
        $debug_stmt = $pdo->prepare("
            SELECT * FROM surveys 
            WHERE id = :survey_id
        ");
        $debug_stmt->execute([':survey_id' => $survey_id]);
        $debug_survey = $debug_stmt->fetch(PDO::FETCH_ASSOC);

        if ($debug_survey) {
            die("Survey exists but does not meet the conditions: " . json_encode($debug_survey));
        } else {
            die("Survey not found.");
        }
    }

    // Initialize the survey array
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
} catch (Exception $e) {
    error_log("Error validating survey access: " . $e->getMessage());
    die("An error occurred while loading the survey.");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Collect all answers for JSON storage
        $answers = [];
        foreach ($survey['questions'] as $question) {
            $field_id = $question['id'];
            $value = $_POST['field_' . $field_id] ?? null;

            // Validate required fields
            if ($question['required'] && (empty($value) && $value !== "0")) {
                throw new Exception("Required question '{$question['label']}' was not answered");
            }

            $answers[$field_id] = is_array($value) ? $value : (string)$value;
        }

        // If the survey is not anonymous, validate and collect the email
        $email = null;
        if (!$survey['is_anonymous']) {
            $email = $_POST['email'] ?? null;
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("A valid email address is required for this survey.");
            }
        }

        // Determine user_id for public surveys
        $user_id = $_SESSION['user_id'] ?? null; // Use NULL for anonymous/public users

        // Insert survey response with JSON answers and email
        $stmt = $pdo->prepare("
            INSERT INTO survey_responses 
            (survey_id, user_id, email, submitted_at, answers) 
            VALUES (:survey_id, :user_id, :email, NOW(), :answers)
        ");
        $stmt->execute([
            ':survey_id' => $survey_id,
            ':user_id' => $user_id,
            ':email' => $email,
            ':answers' => json_encode($answers, JSON_UNESCAPED_UNICODE)
        ]);
        $response_id = $pdo->lastInsertId();

        // Insert individual answers into response_data
        foreach ($survey['questions'] as $question) {
            $field_id = $question['id'];
            $value = $_POST['field_' . $field_id] ?? null;
            if (!empty($value) || $value === "0") {
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $val) {
                    if ($val !== "" && $val !== null) {
                        $stmt = $pdo->prepare("
                            INSERT INTO response_data 
                            (response_id, survey_id, field_id, field_value) 
                            VALUES (:response_id, :survey_id, :field_id, :field_value)
                        ");
                        $stmt->execute([
                            ':response_id' => $response_id,
                            ':survey_id' => $survey_id,
                            ':field_id' => $field_id,
                            ':field_value' => $val
                        ]);
                    }
                }
            }
        }

        $pdo->commit();
        // Store the success message in the session
        $_SESSION['success'] = "Thank you for completing the survey!";
        header("Location: survey.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving survey response: " . $e->getMessage());
        die("An error occurred while submitting the survey. Please try again later. Debug Info: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey['title']) ?> - Survey</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .survey-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .survey-title {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .survey-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        .question-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .form-label {
            font-weight: 500;
            color: #36414c;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            background: #f5f7fa;
            color: #36414c;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            background: #fff;
        }
        .form-check {
            margin-bottom: 8px;
        }
        .form-check-label {
            margin-left: 5px;
        }
        .btn-submit {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background: #0056b3;
        }
        .anonymous-notice {
            background: #e7f5fe;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            font-size: 14px;
        }
        .options-list {
            list-style: none;
            padding-left: 0;
        }
        .options-list li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/includes/public_header.php'; ?>

    <div class="survey-container">
        <h1 class="survey-title"><?= htmlspecialchars($survey['title']) ?></h1>
        <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
        
        <form method="POST">
            <?php if ($survey['is_anonymous']): ?>
                <div class="anonymous-notice">
                    <i class="fas fa-user-secret"></i> This survey is anonymous. Your responses will not be linked to your identity.
                </div>
            <?php else: ?>
                <div class="question-group">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
            <?php endif; ?>
            
            <?php foreach ($survey['questions'] as $question): ?>
                <div class="question-group">
                    <label class="form-label">
                        <?= htmlspecialchars($question['label']) ?>
                        <?php if ($question['required']): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php switch ($question['type']):
                        case 'text': ?>
                            <input type="text" name="field_<?= $question['id'] ?>" class="form-control" <?= $question['required'] ? 'required' : '' ?>>
                            <?php break; 
                        case 'textarea': ?>
                            <textarea name="field_<?= $question['id'] ?>" class="form-control" <?= $question['required'] ? 'required' : '' ?>></textarea>
                            <?php break;  
                        case 'radio': ?>
                            <ul class="options-list">
                                <?php foreach ($question['options'] as $option): ?>
                                    <li class="form-check">
                                        <input type="radio" name="field_<?= $question['id'] ?>" value="<?= htmlspecialchars($option) ?>" <?= $question['required'] ? 'required' : '' ?>>
                                        <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php break; 
                        case 'checkbox': ?>
                            <ul class="options-list">
                                <?php foreach ($question['options'] as $option): ?>
                                    <li class="form-check">
                                        <input type="checkbox" name="field_<?= $question['id'] ?>[]" value="<?= htmlspecialchars($option) ?>">
                                        <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php break; 
                        case 'select': ?>
                            <select name="field_<?= $question['id'] ?>" class="form-control" <?= $question['required'] ? 'required' : '' ?>>
                                <option value="">-- Select --</option>
                                <?php foreach ($question['options'] as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php break; 
                    endswitch; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="text-center">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Survey
                </button>
            </div>
        </form>
    </div>
</body>
</html>

<?php require_once __DIR__ . '/footer.php';
ob_end_flush(); // Flush the output buffer
?>