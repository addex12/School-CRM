<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
ob_start(); // Start output buffering
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid survey ID.";
    header("Location: surveys.php");
    exit();
}

$survey_id = $_GET['id'];

// Fetch survey details with target roles and questions
try {
    // Get survey basic info and target roles
    $stmt = $pdo->prepare("
        SELECT s.*, GROUP_CONCAT(sr.role_id) AS target_roles
        FROM surveys s
        LEFT JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch();

    if (!$survey) {
        $_SESSION['error'] = "Survey not found.";
        header("Location: surveys.php");
        exit();
    }

    // Get survey questions/fields
    $stmt = $pdo->prepare("
        SELECT id, field_type, field_label, field_options, is_required, display_order
        FROM survey_fields
        WHERE survey_id = ?
        ORDER BY display_order
    ");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll();

    // Get all available roles
    $roles = $pdo->query("SELECT * FROM roles")->fetchAll();

    // Get all categories
    $categories = $pdo->query("SELECT * FROM survey_categories")->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: surveys.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update survey basic info
        $stmt = $pdo->prepare("
            UPDATE surveys 
            SET title = ?, description = ?, category_id = ?, 
                starts_at = ?, ends_at = ?, is_active = ?, is_anonymous = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            $_POST['starts_at'],
            $_POST['ends_at'],
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['is_anonymous']) ? 1 : 0,
            $survey_id
        ]);

        // Update target roles
        $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
        $stmt->execute([$survey_id]);

        if (!empty($_POST['target_roles'])) {
            $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
            foreach ($_POST['target_roles'] as $role_id) {
                $stmt->execute([$survey_id, $role_id]);
            }
        }

        // Delete existing questions first to avoid duplicate key issues
        $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
        $stmt->execute([$survey_id]);

        // Insert new questions if they exist
        if (!empty($_POST['questions'])) {
            $stmt = $pdo->prepare("
                INSERT INTO survey_fields 
                (survey_id, field_type, field_label, field_options, is_required, display_order) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['questions'] as $index => $question_text) {
                // Skip empty questions
                if (empty(trim($question_text))) continue;
                
                $options = isset($_POST['options'][$index]) ? 
                    json_encode(array_filter(array_map('trim', explode("\n", $_POST['options'][$index])))) : 
                    null;
                
                $stmt->execute([
                    $survey_id,
                    $_POST['field_types'][$index],
                    $question_text,
                    $options,
                    isset($_POST['required'][$index]) ? 1 : 0,
                    $index
                ]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Survey updated successfully!";
        header("Location: surveys.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating survey: " . $e->getMessage();
        error_log("Survey update error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Survey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
        }
        .admin-main {
            margin-left: 260px;
            padding: 2rem 2.5rem;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 2.5rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #215967;
        }
        input[type="text"], textarea, select, input[type="datetime-local"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
        }
        textarea {
            min-height: 100px;
        }
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            border: 1px solid #fca5a5;
        }
        .success-message {
            background: #dcfce7;
            color: #16a34a;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            border: 1px solid #bbf7d0;
        }
        .btn, .btn-primary, .btn-secondary {
            display: inline-block;
            padding: 10px 22px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #215967;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            margin-right: 4px;
            text-decoration: none;
        }
        .btn-primary {
            background: #3b82f6;
            color: #fff;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #eaeaea;
            color: #666;
        }
        .btn-secondary:hover {
            background: #e2efda;
            color: #215967;
        }
        .role-checkbox {
            display: inline-block;
            margin-right: 15px;
        }
        .question-box {
            border: 1px solid #e5e7eb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 600;
            color: #215967;
        }
        .question-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .options-group {
            grid-column: span 2;
        }
        .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .add-question {
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .container, .admin-main { padding: 1rem; }
            .question-content { grid-template-columns: 1fr; gap: 10px; }
        }
        @media (max-width: 600px) {
            .container, .admin-main { padding: 4px; }
            .question-header { flex-direction: column; gap: 6px; align-items: flex-start; }
            .question-content { grid-template-columns: 1fr; gap: 8px; }
            .btn, .btn-primary { padding: 6px 10px; font-size: 0.95em; }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="admin-main">
        <div class="container">
            <h1 style="color:#215967;font-weight:700;">Edit Survey</h1>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="title">Survey Title *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($survey['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>" <?= $category['id'] == $survey['category_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Roles *</label>
                    <div>
                        <?php foreach ($roles as $role): ?>
                            <div class="role-checkbox">
                                <label>
                                    <input type="checkbox" name="target_roles[]" value="<?= $role['id']; ?>" 
                                        <?= in_array($role['id'], explode(',', $survey['target_roles'] ?? '')) ? 'checked' : ''; ?>>
                                    <?= htmlspecialchars($role['role_name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="starts_at">Start Date/Time *</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" 
                           value="<?= date('Y-m-d\TH:i', strtotime($survey['starts_at'])); ?>" required>
                </div>
                <div class="form-group">
                    <label for="ends_at">End Date/Time *</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" 
                           value="<?= date('Y-m-d\TH:i', strtotime($survey['ends_at'])); ?>" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" <?= $survey['is_active'] ? 'checked' : ''; ?>>
                        Active Survey
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_anonymous" <?= $survey['is_anonymous'] ? 'checked' : ''; ?>>
                        Anonymous Responses
                    </label>
                </div>
                <h2 style="color:#215967;">Survey Questions</h2>
                <div id="questions-container">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-box">
                            <div class="question-header">
                                <span>Question <?= $index + 1 ?></span>
                                <button type="button" class="btn btn-secondary remove-question">Remove</button>
                            </div>
                            <div class="question-content">
                                <div class="form-group">
                                    <label>Question Text *</label>
                                    <input type="text" name="questions[]" value="<?= htmlspecialchars($question['field_label']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Field Type *</label>
                                    <select name="field_types[]" required>
                                        <option value="text" <?= $question['field_type'] == 'text' ? 'selected' : ''; ?>>Text</option>
                                        <option value="textarea" <?= $question['field_type'] == 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                                        <option value="radio" <?= $question['field_type'] == 'radio' ? 'selected' : ''; ?>>Radio</option>
                                        <option value="checkbox" <?= $question['field_type'] == 'checkbox' ? 'selected' : ''; ?>>Checkbox</option>
                                        <option value="select" <?= $question['field_type'] == 'select' ? 'selected' : ''; ?>>Dropdown</option>
                                        <option value="number" <?= $question['field_type'] == 'number' ? 'selected' : ''; ?>>Number</option>
                                        <option value="date" <?= $question['field_type'] == 'date' ? 'selected' : ''; ?>>Date</option>
                                        <option value="rating" <?= $question['field_type'] == 'rating' ? 'selected' : ''; ?>>Rating</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="required[]" <?= $question['is_required'] ? 'checked' : ''; ?>>
                                        Required
                                    </label>
                                </div>
                                <div class="form-group options-group">
                                    <label>Options (for radio, checkbox, dropdown)</label>
                                    <textarea name="options[]" rows="3"><?= 
                                        $question['field_options'] ? 
                                        htmlspecialchars(implode("\n", json_decode($question['field_options']))) : 
                                        '' 
                                    ?></textarea>
                                    <p class="help-text">Enter each option on a new line</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-question" class="btn btn-secondary add-question">Add Question</button>
                <div class="form-group" style="margin-top:2rem;">
                    <button type="submit" class="btn btn-primary">Update Survey</button>
                    <a href="surveys.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add question button
            document.getElementById('add-question').addEventListener('click', function() {
                const container = document.getElementById('questions-container');
                const index = container.children.length;
                const questionBox = document.createElement('div');
                questionBox.className = 'question-box';
                questionBox.innerHTML = `
                    <div class="question-header">
                        <span>Question ${index + 1}</span>
                        <button type="button" class="btn btn-secondary remove-question">Remove</button>
                    </div>
                    <div class="question-content">
                        <div class="form-group">
                            <label>Question Text *</label>
                            <input type="text" name="questions[]" required>
                        </div>
                        <div class="form-group">
                            <label>Field Type *</label>
                            <select name="field_types[]" required>
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="radio">Radio</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="select">Dropdown</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="rating">Rating</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="required[]">
                                Required
                            </label>
                        </div>
                        <div class="form-group options-group">
                            <label>Options (for radio, checkbox, dropdown)</label>
                            <textarea name="options[]" rows="3"></textarea>
                            <p class="help-text">Enter each option on a new line</p>
                        </div>
                    </div>
                `;
                container.appendChild(questionBox);
            });
            // Remove question button
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-question')) {
                    e.target.closest('.question-box').remove();
                    // Reindex remaining questions
                    const questions = document.querySelectorAll('#questions-container .question-box');
                    questions.forEach((question, index) => {
                        question.querySelector('.question-header span').textContent = `Question ${index + 1}`;
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush();?>
