<?php
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['survey_id'] ?? 0;

// Get survey info
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: surveys.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_questions'])) {
        // First, delete existing questions for this survey
        $pdo->prepare("DELETE FROM questions WHERE survey_id = ?")->execute([$survey_id]);
        
        // Insert new questions
        if (!empty($_POST['questions'])) {
            foreach ($_POST['questions'] as $question) {
                $options = null;
                if ($question['type'] === 'multiple_choice' && !empty($question['options'])) {
                    $options = json_encode(array_values($question['options']));
                }
                
                $stmt = $pdo->prepare("INSERT INTO questions (survey_id, question_text, question_type, options, is_required) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $survey_id,
                    $question['text'],
                    $question['type'],
                    $options,
                    isset($question['required']) ? 1 : 0
                ]);
            }
        }
        
        $success = "Questions saved successfully!";
    }
}

// Get existing questions for this survey
$stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY id");
$stmt->execute([$survey_id]);
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions - Parent Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Questions: <?php echo htmlspecialchars($survey['title']); ?></h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="surveys.php">Surveys</a>
                <a href="results.php">Results</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="content">
            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div id="questions-container">
                    <?php if (count($questions) > 0): ?>
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="question-item">
                                <div class="question-header">
                                    <h3>Question #<?php echo $index + 1; ?></h3>
                                    <button type="button" class="delete-question">Delete</button>
                                </div>
                                
                                <div class="form-group">
                                    <label>Question Text:</label>
                                    <textarea name="questions[<?php echo $index + 1; ?>][text]" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Question Type:</label>
                                    <select name="questions[<?php echo $index + 1; ?>][type]" class="question-type" required>
                                        <option value="multiple_choice" <?php echo $question['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                        <option value="text" <?php echo $question['question_type'] === 'text' ? 'selected' : ''; ?>>Text Answer</option>
                                        <option value="rating" <?php echo $question['question_type'] === 'rating' ? 'selected' : ''; ?>>Rating (1-5)</option>
                                    </select>
                                </div>
                                
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <div class="form-group question-options">
                                        <label>Options (for multiple choice):</label>
                                        <div class="options-container">
                                            <?php 
                                            $options = json_decode($question['options'], true);
                                            if (is_array($options)): 
                                                foreach ($options as $optIndex => $option): ?>
                                                    <div class="option-container">
                                                        <input type="text" class="option-input" name="questions[<?php echo $index + 1; ?>][options][<?php echo $optIndex; ?>]" value="<?php echo htmlspecialchars($option); ?>" placeholder="Option <?php echo $optIndex + 1; ?>" required>
                                                        <button type="button" class="delete-option">×</button>
                                                    </div>
                                                <?php endforeach; 
                                            endif; ?>
                                        </div>
                                        <button type="button" class="add-option">Add Option</button>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="questions[<?php echo $index + 1; ?>][required]" <?php echo $question['is_required'] ? 'checked' : ''; ?>>
                                        Required Question
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-questions">No questions yet. Click "Add Question" to get started.</p>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="add-question" class="btn">Add Question</button>
                    <button type="submit" name="save_questions" class="btn btn-primary">Save All Questions</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>