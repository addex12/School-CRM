<?php
require_once '../includes/config.php'; // Include config to initialize $pdo
require_once '../includes/auth.php';
requireLogin();

$pageTitle = "Manage Surveys"; // Set the page title
include 'includes/header.php'; // Replace existing header
// Get survey info
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND is_active = TRUE");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php");
    exit();
}

// Check if user has already completed this survey
$stmt = $pdo->prepare("SELECT COUNT(*) FROM responses WHERE survey_id = ? AND user_id = ?");
$stmt->execute([$survey_id, $_SESSION['user_id']]);
$completed = $stmt->fetchColumn() > 0;

if ($completed) {
    header("Location: dashboard.php");
    exit();
}

// Get questions for this survey
$stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY id");
$stmt->execute([$survey_id]);
$questions = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    
    foreach ($questions as $question) {
        $response = $_POST['responses'][$question['id']] ?? '';
        
        // Validate required questions
        if ($question['is_required'] && empty($response)) {
            $success = false;
            $error = "Please answer all required questions";
            break;
        }
        
        // Validate rating questions
        if ($question['question_type'] === 'rating' && !empty($response)) {
            $rating = intval($response);
            if ($rating < 1 || $rating > 5) {
                $success = false;
                $error = "Please provide a valid rating between 1 and 5";
                break;
            }
        }
    }
    
    if ($success) {
        // Save responses
        foreach ($questions as $question) {
            $response = $_POST['responses'][$question['id']] ?? null;
            
            if ($response !== null) {
                $stmt = $pdo->prepare("INSERT INTO responses (survey_id, question_id, user_id, response) VALUES (?, ?, ?, ?)");
                $stmt->execute([$survey_id, $question['id'], $_SESSION['user_id'], $response]);
            }
        }
        
        header("Location: dashboard.php?completed=1");
        exit();
    }
}
 include 'includes/header.php'; // Include header 
 ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey: <?php echo htmlspecialchars($survey['title']); ?> - Parent Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">       
        <div class="survey-content">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="survey-description">
                <p><?php echo htmlspecialchars($survey['description']); ?></p>
            </div>
            
            <form id="survey-form" method="POST">
                <?php foreach ($questions as $question): ?>
                    <div class="question" data-required="<?php echo $question['is_required'] ? 'true' : 'false'; ?>">
                        <h3>
                            <?php echo htmlspecialchars($question['question_text']); ?>
                            <?php if ($question['is_required']): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </h3>
                        
                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                            <?php 
                            $options = json_decode($question['options'], true);
                            if (is_array($options)): ?>
                                <div class="options">
                                    <?php foreach ($options as $index => $option): ?>
                                        <label class="option">
                                            <input type="radio" name="responses[<?php echo $question['id']; ?>]" value="<?php echo htmlspecialchars($option); ?>" <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($question['question_type'] === 'text'): ?>
                            <textarea name="responses[<?php echo $question['id']; ?>]" <?php echo $question['is_required'] ? 'required' : ''; ?>></textarea>
                        <?php elseif ($question['question_type'] === 'rating'): ?>
                            <div class="rating-container">
                                <input type="hidden" name="responses[<?php echo $question['id']; ?>]" value="">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="rating-star" data-value="<?php echo $i; ?>">★</span>
                                <?php endfor; ?>
                                <div class="rating-labels">
                                    <span>1 (Poor)</span>
                                    <span>5 (Excellent)</span>
                                </div>
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
    
    <script src="../assets/js/script.js"></script>
</body>
</html>