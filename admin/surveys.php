<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

// Get survey_id from URL parameter
$survey_id = $_GET['id'] ?? null;

// Validate survey_id
if (!$survey_id || !is_numeric($survey_id)) {
    header("Location: dashboard.php?error=invalid_survey");
        exit();
}

// Optional: Check if survey is active only if needed
if (!$survey['is_active']) {
    header("Location: dashboard.php?error=survey_inactive");
    exit();
}
$pageTitle = "Manage Surveys";
ob_start(); // Start output buffering to prevent header errors
include 'includes/header.php';

// Get survey info
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND is_active = TRUE");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php?error=survey_not_found");
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
$error = null;
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
ob_end_flush(); // End output buffering and send output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey: <?php echo htmlspecialchars($survey['title']); ?> - Parent Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .question {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .required {
            color: red;
        }
        .options {
            margin-top: 1rem;
        }
        .option {
            display: block;
            margin: 0.5rem 0;
        }
        .rating-container {
            margin-top: 1rem;
        }
        .rating-star {
            font-size: 2rem;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-star:hover,
        .rating-star.active {
            color: gold;
        }
        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #666;
        }
        .error-message {
            color: red;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #ffeeee;
            border: 1px solid #ffcccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">       
        <div class="survey-content">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <h1><?php echo htmlspecialchars($survey['title']); ?></h1>
            
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
                                            <input type="radio" name="responses[<?php echo $question['id']; ?>]" 
                                                   value="<?php echo htmlspecialchars($option); ?>" 
                                                   <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($question['question_type'] === 'text'): ?>
                            <textarea name="responses[<?php echo $question['id']; ?>]" 
                                      rows="4" 
                                      style="width: 100%;"
                                      <?php echo $question['is_required'] ? 'required' : ''; ?>></textarea>
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
    
    <script>
        // Rating star functionality
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const container = this.closest('.rating-container');
                const hiddenInput = container.querySelector('input[type="hidden"]');
                
                // Update hidden input value
                hiddenInput.value = value;
                
                // Update star display
                container.querySelectorAll('.rating-star').forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });

        // Form validation
        document.getElementById('survey-form').addEventListener('submit', function(e) {
            const requiredQuestions = document.querySelectorAll('.question[data-required="true"]');
            let isValid = true;
            
            requiredQuestions.forEach(question => {
                const input = question.querySelector('input[type="radio"]:checked, textarea, input[type="hidden"][value]');
                if (!input || (input.tagName === 'TEXTAREA' && !input.value.trim())) {
                    isValid = false;
                    question.style.border = '1px solid red';
                } else {
                    question.style.border = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please answer all required questions');
            }
        });
    </script>
</body>
</html>