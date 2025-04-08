<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../models/Survey.php';

$pageTitle = "Survey Builder";

// Fetch survey details if editing an existing survey
$survey_id = $_GET['id'] ?? null;
$survey = null;
if ($survey_id) {
    $survey = Survey::model($pdo)->findByPk($survey_id);
}

// Fetch roles dynamically from the database
try {
    $rolesStmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name");
    $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching roles: " . $e->getMessage());
    $roles = [];
}

// Fetch categories dynamically from the database
try {
    $categoriesStmt = $pdo->query("SELECT id, name FROM survey_categories ORDER BY name");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Fetch statuses dynamically from the database
try {
    $statusesStmt = $pdo->query("SELECT id, status, label FROM survey_statuses ORDER BY id");
    $statuses = $statusesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching statuses: " . $e->getMessage());
    $statuses = [];
}

// Initialize survey model with PDO connection
$surveyModel = Survey::model($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $survey_data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => $_POST['category_id'] ?? null,
        'status' => $_POST['status'] ?? null,
        'is_active' => isset($_POST['is_active']),
        'is_anonymous' => isset($_POST['is_anonymous']),
        'target_roles' => $_POST['target_roles'] ?? [],
        'questions' => $_POST['questions'] ?? [],
        'field_types' => $_POST['field_types'] ?? [],
        'options' => $_POST['options'] ?? [],
        'required' => $_POST['required'] ?? []
    ];

    if ($survey_id) {
        $survey_data['id'] = $survey_id;
    }

    $result = $surveyModel->save($survey_data);

    if ($result['success']) {
        $_SESSION['success'] = $survey_id ? "Survey updated successfully!" : "Survey created successfully!";
        header("Location: surveys.php");
        exit();
    } else {
        $_SESSION['error'] = implode("\n", $result['errors']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <style>
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fb;
        }

        .admin-main {
            flex: 1;
            margin-left: 280px; /* Matches the sidebar width */
            padding: 20px 30px;
        }

        .form-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-group textarea {
            resize: vertical;
        }

        .form-actions {
            margin-top: 20px;
        }

        .form-actions button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .ai-suggestions {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }

        .role-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .role-checkbox input[type="checkbox"] {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>

            <div class="form-container">
                <h2><?= $survey ? "Edit Survey" : "Create New Survey" ?></h2>
                <form id="survey-form" method="POST">
                    <div class="form-group">
                        <label for="title">Survey Title</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Survey Description</label>
                        <textarea id="description" name="description" rows="5"><?= htmlspecialchars($survey['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['id']) ?>" <?= $category['id'] == ($survey['category_id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= htmlspecialchars($status['status']) ?>" <?= $status['status'] == ($survey['status'] ?? 'draft') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="target_roles">Target Roles</label>
                        <div class="roles-grid">
                            <?php foreach ($roles as $role): ?>
                                <div class="role-checkbox">
                                    <label>
                                        <input type="checkbox" name="target_roles[]" value="<?= htmlspecialchars($role['id']) ?>" 
                                               <?= in_array($role['id'], $survey['target_roles'] ?? []) ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?= isset($survey['is_active']) && $survey['is_active'] ? 'checked' : '' ?>>
                            Active
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_anonymous" <?= isset($survey['is_anonymous']) && $survey['is_anonymous'] ? 'checked' : '' ?>>
                            Allow Anonymous Responses
                        </label>
                    </div>
                    <div id="questions-container">
                        <?php if ($survey && isset($survey['questions'])): ?>
                            <?php foreach ($survey['questions'] as $index => $question): ?>
                                <div class="question-row" data-index="<?= $index ?>">
                                    <div class="form-group">
                                        <input type="text" name="questions[<?= $index ?>]" 
                                               value="<?= htmlspecialchars($question['question']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <select name="field_types[<?= $index ?>]" required>
                                            <option value="text" <?= $question['field_type'] == 'text' ? 'selected' : '' ?>>Text</option>
                                            <option value="radio" <?= $question['field_type'] == 'radio' ? 'selected' : '' ?>>Multiple Choice (Single)</option>
                                            <option value="checkbox" <?= $question['field_type'] == 'checkbox' ? 'selected' : '' ?>>Multiple Choice (Multiple)</option>
                                            <option value="select" <?= $question['field_type'] == 'select' ? 'selected' : '' ?>>Dropdown</option>
                                        </select>
                                    </div>
                                    <?php if (in_array($question['field_type'], ['radio', 'checkbox', 'select'])): ?>
                                        <div class="form-group options-group">
                                            <label>Options:</label>
                                            <textarea name="options[<?= $index ?>]" rows="3"><?= htmlspecialchars($question['options'] ?? '') ?></textarea>
                                            <p class="help-text">Enter each option on a new line</p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="required[<?= $index ?>]" <?= isset($question['is_required']) && $question['is_required'] ? 'checked' : '' ?>>
                                            Required
                                        </label>
                                    </div>
                                    <button type="button" class="remove-question btn-danger">Remove Question</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="add-question" class="btn-primary">Add Question</button>
                        <button type="submit" class="btn-primary">Save Survey</button>
                        <a href="surveys.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>

                
        </div>
    </div>
    <script src="../assets/js/survey_builder.js"></script>
    <script>
        $(document).ready(function() {
            let questionIndex = <?= isset($survey['questions']) ? count($survey['questions']) : 0 ?>;

            // Add question button click handler
            $('#add-question').click(function() {
                const questionRow = `
                    <div class="question-row" data-index="${questionIndex}">
                        <div class="form-group">
                            <input type="text" name="questions[${questionIndex}]" required placeholder="Enter question text">
                        </div>
                        <div class="form-group">
                            <select name="field_types[${questionIndex}]" required>
                                <option value="text">Text</option>
                                <option value="radio">Multiple Choice (Single)</option>
                                <option value="checkbox">Multiple Choice (Multiple)</option>
                                <option value="select">Dropdown</option>
                            </select>
                        </div>
                        <div class="form-group options-group">
                            <label>Options:</label>
                            <textarea name="options[${questionIndex}]" rows="3" placeholder="Enter each option on a new line"></textarea>
                            <p class="help-text">Enter each option on a new line</p>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="required[${questionIndex}]">
                                Required
                            </label>
                        </div>
                        <button type="button" class="remove-question btn-danger">Remove Question</button>
                    </div>
                `;

                $('#questions-container').append(questionRow);
                questionIndex++;
            });

            // Remove question button click handler
            $(document).on('click', '.remove-question', function() {
                $(this).closest('.question-row').remove();
            });

            // Show/hide options textarea based on field type
            $(document).on('change', '[name^="field_types"]', function() {
                const fieldType = $(this).val();
                const optionsGroup = $(this).closest('.question-row').find('.options-group');
                
                if (['radio', 'checkbox', 'select'].includes(fieldType)) {
                    optionsGroup.show();
                } else {
                    optionsGroup.hide();
                }
            });

            // Initialize existing options groups
            $('[name^="field_types"]').trigger('change');
        });
    </script>
</body>
</html>
<?php include 'includes/footer.php'; ?>
