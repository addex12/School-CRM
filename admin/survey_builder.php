<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Add field types configuration
$fieldTypes = [
    'text' => 'Text Input',
    'textarea' => 'Text Area',
    'radio' => 'Radio Buttons',
    'checkbox' => 'Checkboxes',
    'dropdown' => 'Dropdown',
    'number' => 'Number',
    'date' => 'Date',
    'rating' => 'Rating',
    'file' => 'File Upload'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Save survey basic info
        $stmt = $pdo->prepare("INSERT INTO surveys 
            (title, description, category_id, target_roles, status, starts_at, ends_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            json_encode($_POST['target_roles']),
            $_POST['status'],
            $_POST['starts_at'],
            $_POST['ends_at'],
            $_SESSION['user_id']
        ]);

        $surveyId = $pdo->lastInsertId();

        // Save fields
        if (!empty($_POST['questions'])) {
            foreach ($_POST['questions'] as $index => $question) {
                $options = null;
                if (in_array($_POST['field_types'][$index], ['radio', 'checkbox', 'dropdown'])) {
                    $options = json_encode(array_map('trim', explode(',', $_POST['options'][$index])));
                }

                $stmt = $pdo->prepare("INSERT INTO survey_fields 
                    (survey_id, field_type, field_label, field_name, placeholder, field_options, is_required, display_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $surveyId,
                    $_POST['field_types'][$index],
                    $question,
                    'field_' . ($index + 1),
                    $_POST['placeholders'][$index] ?? '',
                    $options,
                    isset($_POST['required'][$index]) ? 1 : 0,
                    $index
                ]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Survey created successfully!";
        header("Location: survey_preview.php?id=$surveyId");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error creating survey: " . $e->getMessage();
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Builder - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .survey-field { margin-bottom: 1.5rem; padding: 1rem; background: #f9f9f9; border-radius: 5px; }
        .field-options { display: none; margin-top: 1rem; }
        .form-actions { margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Survey Builder</h1>
        </header>

        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form id="survey-form" method="POST">
                <div class="form-section">
                    <h2>Survey Information</h2>
                    <div class="form-group">
                        <label for="title">Survey Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Target Audience:</label>
                        <div>
                            <label><input type="checkbox" name="target_roles[]" value="student" checked> Students</label>
                            <label><input type="checkbox" name="target_roles[]" value="teacher" checked> Teachers</label>
                            <label><input type="checkbox" name="target_roles[]" value="parent" checked> Parents</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="starts_at">Start Date/Time:</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" required>
                        </div>
                        <div class="form-group">
                            <label for="ends_at">End Date/Time:</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Survey Questions</h2>
                    <div id="survey-fields">
                        <div class="survey-field">
                            <label for="question-1">Question 1</label>
                            <input type="text" name="questions[]" id="question-1" placeholder="Enter your question" required>
                            <select name="field_types[]" class="field-type-selector" required>
                                <?php foreach ($fieldTypes as $type => $label): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="placeholders[]" placeholder="Placeholder (optional)">
                            <div class="field-options">
                                <label>Options (comma-separated):</label>
                                <input type="text" name="options[]" placeholder="Option1, Option2, Option3">
                            </div>
                            <label><input type="checkbox" name="required[]"> Required</label>
                            <button type="button" class="remove-field btn btn-danger">Remove</button>
                        </div>
                    </div>
                    <button type="button" id="add-field" class="btn btn-primary">Add Question</button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Save Survey</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('add-field').addEventListener('click', function () {
            const fieldCount = document.querySelectorAll('.survey-field').length + 1;
            const fieldHTML = `
                <div class="survey-field">
                    <label for="question-${fieldCount}">Question ${fieldCount}</label>
                    <input type="text" name="questions[]" id="question-${fieldCount}" placeholder="Enter your question" required>
                    <select name="field_types[]" class="field-type-selector" required>
                        <?php foreach ($fieldTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="placeholders[]" placeholder="Placeholder (optional)">
                    <div class="field-options">
                        <label>Options (comma-separated):</label>
                        <input type="text" name="options[]" placeholder="Option1, Option2, Option3">
                    </div>
                    <label><input type="checkbox" name="required[]"> Required</label>
                    <button type="button" class="remove-field btn btn-danger">Remove</button>
                </div>`;
            document.getElementById('survey-fields').insertAdjacentHTML('beforeend', fieldHTML);
        });

        document.getElementById('survey-fields').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-field')) {
                e.target.closest('.survey-field').remove();
            }
        });

        document.getElementById('survey-fields').addEventListener('change', function (e) {
            if (e.target.classList.contains('field-type-selector')) {
                const optionsDiv = e.target.closest('.survey-field').querySelector('.field-options');
                if (['radio', 'checkbox', 'dropdown'].includes(e.target.value)) {
                    optionsDiv.style.display = 'block';
                } else {
                    optionsDiv.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>