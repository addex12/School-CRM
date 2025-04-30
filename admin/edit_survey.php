<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
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
                starts_at = ?, ends_at = ?, is_active = ?, is_public = ?, is_anonymous = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            $_POST['starts_at'],
            $_POST['ends_at'],
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['is_public']) ? 1 : 0,
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
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 900px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.25em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-form-group {
            margin-bottom: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.2em;
        }
        .adugna-form-group label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input,
        .adugna-form-group textarea,
        .adugna-form-group select {
            padding: 7px 10px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-btn-sm { padding: 2px 7px; font-size: 0.93em; border-radius: 3px; }
        .adugna-role-checkbox {
            display: inline-block;
            margin-right: 15px;
            font-size: 0.97em;
        }
        .adugna-question-box {
            border: 1px solid #e5e7eb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .adugna-question-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 600;
            color: #215967;
        }
        .adugna-question-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .adugna-options-group {
            grid-column: span 2;
        }
        .adugna-help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .adugna-add-question {
            margin-bottom: 20px;
        }
        .adugna-error-message {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        .adugna-success-message {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        @media (max-width: 900px) {
            .adugna-main-content { padding: 1rem; }
            .adugna-question-content { grid-template-columns: 1fr; gap: 10px; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 4px; }
            .adugna-question-header { flex-direction: column; gap: 6px; align-items: flex-start; }
            .adugna-question-content { grid-template-columns: 1fr; gap: 8px; }
            .adugna-btn, .adugna-btn-primary { padding: 6px 10px; font-size: 0.95em; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
    <div class="adugna-main-content">
        <div class="adugna-header-title">
            <i class="fas fa-edit"></i> Edit Survey
        </div>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="adugna-error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="adugna-form-group">
                <label for="title">Survey Title *</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title']); ?>" required>
            </div>
            <div class="adugna-form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars($survey['description']); ?></textarea>
            </div>
            <div class="adugna-form-group">
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
            <div class="adugna-form-group">
                <label>Target Roles *</label>
                <div>
                    <?php foreach ($roles as $role): ?>
                        <div class="adugna-role-checkbox">
                            <label>
                                <input type="checkbox" name="target_roles[]" value="<?= $role['id']; ?>" 
                                    <?= in_array($role['id'], explode(',', $survey['target_roles'] ?? '')) ? 'checked' : ''; ?>>
                                <?= htmlspecialchars($role['role_name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="adugna-form-group">
                <label for="starts_at">Start Date/Time *</label>
                <input type="datetime-local" id="starts_at" name="starts_at" 
                       value="<?= date('Y-m-d\TH:i', strtotime($survey['starts_at'])); ?>" required>
            </div>
            <div class="adugna-form-group">
                <label for="ends_at">End Date/Time *</label>
                <input type="datetime-local" id="ends_at" name="ends_at" 
                       value="<?= date('Y-m-d\TH:i', strtotime($survey['ends_at'])); ?>" required>
            </div>
            <div class="adugna-form-group">
                <label>
                    <input type="checkbox" name="is_active" <?= $survey['is_active'] ? 'checked' : ''; ?>>
                    Active Survey
                </label>
            </div>
            <div class="adugna-form-group">
                <label>
                    <input type="checkbox" name="is_anonymous" <?= $survey['is_anonymous'] ? 'checked' : ''; ?>>
                    Anonymous Responses
                </label>
            </div>
            <div class="adugna-form-group">
                <label>
                    <input type="checkbox" name="is_public" <?= $survey['is_public'] ? 'checked' : ''; ?>>
                    Make Survey Public
                </label>
            </div>
            <h2 class="adugna-header-title" style="font-size:1.1em;margin-top:2em;"><i class="fas fa-question-circle"></i> Survey Questions</h2>
            <div id="questions-container">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="adugna-question-box">
                        <div class="adugna-question-header">
                            <span>Question <?= $index + 1 ?></span>
                            <button type="button" class="adugna-btn adugna-btn-secondary adugna-btn-sm remove-question">Remove</button>
                        </div>
                        <div class="adugna-question-content">
                            <div class="adugna-form-group">
                                <label>Question Text *</label>
                                <input type="text" name="questions[]" value="<?= htmlspecialchars($question['field_label']); ?>" required>
                            </div>
                            <div class="adugna-form-group">
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
                            <div class="adugna-form-group">
                                <label>
                                    <input type="checkbox" name="required[]" <?= $question['is_required'] ? 'checked' : ''; ?>>
                                    Required
                                </label>
                            </div>
                            <div class="adugna-form-group adugna-options-group">
                                <label>Options (for radio, checkbox, dropdown)</label>
                                <textarea name="options[]" rows="3"><?= 
                                    $question['field_options'] ? 
                                    htmlspecialchars(implode("\n", json_decode($question['field_options']))) : 
                                    '' 
                                ?></textarea>
                                <p class="adugna-help-text">Enter each option on a new line</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-question" class="adugna-btn adugna-btn-secondary adugna-add-question"><i class="fas fa-plus"></i> Add Question</button>
            <div class="adugna-form-group" style="margin-top:2rem;">
                <button type="submit" class="adugna-btn"><i class="fas fa-save"></i> Update Survey</button>
                <a href="surveys.php" class="adugna-btn adugna-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
        /**
         * Adugna Gizaw: Dynamic add/remove survey question fields, compact and responsive.
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Add question button
            document.getElementById('add-question').addEventListener('click', function() {
                const container = document.getElementById('questions-container');
                const index = container.children.length;
                const questionBox = document.createElement('div');
                questionBox.className = 'adugna-question-box';
                questionBox.innerHTML = `
                    <div class="adugna-question-header">
                        <span>Question ${index + 1}</span>
                        <button type="button" class="adugna-btn adugna-btn-secondary adugna-btn-sm remove-question">Remove</button>
                    </div>
                    <div class="adugna-question-content">
                        <div class="adugna-form-group">
                            <label>Question Text *</label>
                            <input type="text" name="questions[]" required>
                        </div>
                        <div class="adugna-form-group">
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
                        <div class="adugna-form-group">
                            <label>
                                <input type="checkbox" name="required[]">
                                Required
                            </label>
                        </div>
                        <div class="adugna-form-group adugna-options-group">
                            <label>Options (for radio, checkbox, dropdown)</label>
                            <textarea name="options[]" rows="3"></textarea>
                            <p class="adugna-help-text">Enter each option on a new line</p>
                        </div>
                    </div>
                `;
                container.appendChild(questionBox);
            });
            // Remove question button
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-question')) {
                    e.target.closest('.adugna-question-box').remove();
                    // Reindex remaining questions
                    const questions = document.querySelectorAll('#questions-container .adugna-question-box');
                    questions.forEach((question, index) => {
                        question.querySelector('.adugna-question-header span').textContent = `Question ${index + 1}`;
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush();?>
