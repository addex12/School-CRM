<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../models/Survey.php';

$survey_id = $_GET['id'] ?? 0;

// Validate survey access
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               sf.*
        FROM surveys s
        JOIN survey_fields sf ON s.id = sf.survey_id
        JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE s.id = ? 
        AND s.is_active = TRUE 
        AND s.starts_at <= NOW() 
        AND s.ends_at >= NOW()
        AND sr.role_id = ?
        ORDER BY sf.sort_order
    ");
    $stmt->execute([$survey_id, $_SESSION['role_id']]);
    $survey = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($survey)) {
        $_SESSION['error'] = "This survey is not available for your role or is not active.";
        header("Location: dashboard.php");
        exit();
    }
    
    // Check if user has already responded
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM survey_responses sr
        WHERE sr.survey_id = ? AND sr.user_id = ?
    ");
    $stmt->execute([$survey_id, $_SESSION['user_id']]);
    $hasResponded = $stmt->fetchColumn() > 0;
    
    if ($hasResponded && !$survey[0]['is_anonymous']) {
        $_SESSION['error'] = "You have already responded to this survey.";
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error validating survey access: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred. Please try again later.";
    header("Location: dashboard.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insert survey response
        $stmt = $pdo->prepare("
            INSERT INTO survey_responses 
            (survey_id, user_id, response_time, anonymous) 
            VALUES (?, ?, NOW(), ?)
        ");
        $anonymous = isset($_POST['anonymous']) ? 1 : 0;
        $stmt->execute([$survey_id, $anonymous ? null : $_SESSION['user_id'], $anonymous]);
        $response_id = $pdo->lastInsertId();
        
        // Process each question response
        foreach ($survey as $question) {
            $field_type = $question['field_type'];
            $field_id = $question['id'];
            
            if ($question['is_required'] && !isset($_POST[$field_id])) {
                throw new Exception("Question " . $question['question'] . " is required");
            }
            
            $value = $_POST[$field_id] ?? null;
            
            // Handle different field types
            switch ($field_type) {
                case 'checkbox':
                    // Checkbox fields can have multiple values
                    $values = is_array($value) ? $value : [$value];
                    foreach ($values as $val) {
                        if (!empty($val)) {
                            $stmt = $pdo->prepare("
                                INSERT INTO survey_field_responses 
                                (response_id, field_id, value) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$response_id, $field_id, $val]);
                        }
                    }
                    break;
                    
                default:
                    if (!empty($value)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO survey_field_responses 
                            (response_id, field_id, value) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$response_id, $field_id, $value]);
                    }
                    break;
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Thank you for completing the survey!";
        header("Location: dashboard.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error saving survey response: " . $e->getMessage());
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving survey response: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Display survey form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($survey[0]['title']) ?> - Take Survey</title>
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
        .question-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .required {
            color: #dc3545;
            font-size: 0.9em;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-actions {
            margin-top: 20px;
            text-align: center;
        }
        .btn-primary {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="survey-container">
        <h1><?= htmlspecialchars($survey[0]['title']) ?></h1>
        <p class="description"><?= htmlspecialchars($survey[0]['description']) ?></p>
        
        <form method="POST">
            <?php if ($survey[0]['is_anonymous']): ?>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="anonymous" value="1">
                        Submit anonymously
                    </label>
                </div>
            <?php endif; ?>
            
            <?php 
            function renderField($question) {
                $field_id = $question['id'];
                $is_required = $question['is_required'] ? 'required' : '';
                
                switch ($question['field_type']) {
                    case 'text':
                        echo '<input type="text" 
                                   name="' . htmlspecialchars($field_id) . '" 
                                   class="form-control" 
                                   ' . $is_required . '>';
                        break;
                        
                    case 'textarea':
                        echo '<textarea name="' . htmlspecialchars($field_id) . '" 
                                   class="form-control" 
                                   rows="3" 
                                   ' . $is_required . '></textarea>';
                        break;
                        
                    case 'radio':
                        $options = !empty($question['options']) ? explode("\n", $question['options']) : [];
                        foreach ($options as $option) {
                            $option = trim($option);
                            if (!empty($option)) {
                                echo '<div class="form-check">
                                    <input type="radio" 
                                           name="' . htmlspecialchars($field_id) . '" 
                                           value="' . htmlspecialchars($option) . '" 
                                           id="' . htmlspecialchars($field_id . '_' . $option) . '"
                                           class="form-check-input" 
                                           ' . $is_required . '>
                                    <label class="form-check-label" 
                                           for="' . htmlspecialchars($field_id . '_' . $option) . '">
                                        ' . htmlspecialchars($option) . '
                                    </label>
                                </div>';
                            }
                        }
                        break;
                        
                    case 'checkbox':
                        $options = !empty($question['options']) ? explode("\n", $question['options']) : [];
                        foreach ($options as $option) {
                            $option = trim($option);
                            if (!empty($option)) {
                                echo '<div class="form-check">
                                    <input type="checkbox" 
                                           name="' . htmlspecialchars($field_id) . '[]' . '" 
                                           value="' . htmlspecialchars($option) . '" 
                                           id="' . htmlspecialchars($field_id . '_' . $option) . '" 
                                           class="form-check-input">
                                    <label class="form-check-label" 
                                           for="' . htmlspecialchars($field_id . '_' . $option) . '">
                                        ' . htmlspecialchars($option) . '
                                    </label>
                                </div>';
                            }
                        }
                        break;
                        
                    case 'select':
                        echo '<select name="' . htmlspecialchars($field_id) . '" 
                               class="form-control" 
                               ' . $is_required . '>';
                        echo '<option value="">Select an option</option>';
                        
                        $options = !empty($question['options']) ? explode("\n", $question['options']) : [];
                        foreach ($options as $option) {
                            $option = trim($option);
                            if (!empty($option)) {
                                echo '<option value="' . htmlspecialchars($option) . '">' . 
                                     htmlspecialchars($option) . '</option>';
                            }
                        }
                        echo '</select>';
                        break;
                }
            }
            
            foreach ($survey as $question): ?>
                <div class="question-group">
                    <h3><?= htmlspecialchars($question['field_label']) ?></h3>
                    <?php if ($question['is_required']): ?>
                        <p class="required">* Required</p>
                    <?php endif; ?>
                    <div class="form-group">
                        <?php renderField($question); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Submit Survey</button>
            </div>
        </form>
    </div>
</body>
</html>