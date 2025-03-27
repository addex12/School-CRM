<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

// Validate survey_id parameter
$survey_id = $_GET['survey_id'] ?? null;
if ($survey_id !== null) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: dashboard.php?error=invalid_survey");
        exit();
    }
}

$pageTitle = "Manage Surveys";
ob_start(); // Start output buffering to prevent header errors

// Get survey info
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: dashboard.php?error=survey_not_found");
    exit();
}

// Check if the survey is active
if (!$survey['is_active'] || strtotime($survey['starts_at']) > time() || strtotime($survey['ends_at']) < time()) {
    header("Location: dashboard.php?error=survey_inactive");
    exit();
}

// Check if the survey is anonymous
$is_anonymous = $survey['is_anonymous'];

// Check if user has already completed this survey
$stmt = $pdo->prepare("SELECT COUNT(*) FROM responses WHERE survey_id = ? AND user_id = ?");
$stmt->execute([$survey_id, $_SESSION['user_id']]);
$completed = $stmt->fetchColumn() > 0;

if ($completed) {
    header("Location: dashboard.php?error=survey_completed");
    exit();
}

// Fetch questions for this survey
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$questions = $stmt->fetchAll();

// Handle form submission
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $responses = $_POST['responses'] ?? [];

    foreach ($questions as $question) {
        $response = $responses[$question['id']] ?? null;

        // Validate required fields
        if ($question['is_required'] && empty($response)) {
            $success = false;
            $error = "Please answer all required questions.";
            break;
        }

        // Validate rating fields
        if ($question['field_type'] === 'rating' && !empty($response)) {
            $rating = intval($response);
            if ($rating < 1 || $rating > 5) {
                $success = false;
                $error = "Please provide a valid rating between 1 and 5.";
                break;
            }
        }
    }

    if ($success) {
        // Save responses
        foreach ($questions as $question) {
            $response = $responses[$question['id']] ?? null;
            if ($response !== null) {
                $stmt = $pdo->prepare("INSERT INTO responses (survey_id, field_id, user_id, response) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $survey_id,
                    $question['id'],
                    $is_anonymous ? null : $_SESSION['user_id'], // Nullify user_id if anonymous
                    $response
                ]);
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
    <title>Manage Surveys</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="content">
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
            </div>
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