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
    $stmt = $pdo->prepare("
        SELECT s.*, 
               GROUP_CONCAT(DISTINCT r.role_name) as target_roles
        FROM surveys s
        LEFT JOIN survey_roles sr ON s.id = sr.survey_id
        LEFT JOIN roles r ON sr.role_id = r.id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($survey) {
        $survey['target_roles'] = array_map('intval', explode(',', $survey['target_roles']));
    }
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
    // Handle survey data
    $survey_data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => $_POST['category_id'] ?? null,
        'status' => $_POST['status'] ?? null,
        'is_active' => isset($_POST['is_active']),
        'is_anonymous' => isset($_POST['is_anonymous']),
        'starts_at' => date('Y-m-d H:i:s', strtotime($_POST['starts_at'] ?? '+1 day')),
        'ends_at' => date('Y-m-d H:i:s', strtotime($_POST['ends_at'] ?? '+1 month'))
    ];

    // Handle target roles
    $target_roles = isset($_POST['target_roles']) ? array_map('intval', $_POST['target_roles']) : [];

    // Handle questions
    $questions = [];
    if (isset($_POST['questions'])) {
        foreach ($_POST['questions'] as $index => $question) {
            $questions[] = [
                'field_type' => $_POST['field_types'][$index],
                'field_label' => $question,
                'field_options' => $_POST['options'][$index] ?? null,
                'is_required' => isset($_POST['required'][$index])
            ];
        }
    }

    if ($survey_id) {
        $survey_data['id'] = $survey_id;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Save survey
        $stmt = $pdo->prepare("
            INSERT INTO surveys (title, description, category_id, status, is_active, 
                               is_anonymous, starts_at, ends_at, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                title = VALUES(title),
                description = VALUES(description),
                category_id = VALUES(category_id),
                status = VALUES(status),
                is_active = VALUES(is_active),
                is_anonymous = VALUES(is_anonymous),
                starts_at = VALUES(starts_at),
                ends_at = VALUES(ends_at)
        ");
        
        $survey_data['created_by'] = $_SESSION['user_id'];
        $stmt->execute(array_values($survey_data));
        $survey_id = $survey_id ?? $pdo->lastInsertId();

        // Delete existing roles and add new ones
        $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
        $stmt->execute([$survey_id]);

        if (!empty($target_roles)) {
            $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
            foreach ($target_roles as $role_id) {
                $stmt->execute([$survey_id, $role_id]);
            }
        }

        // Delete existing questions and add new ones
        $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
        $stmt->execute([$survey_id]);

        if (!empty($questions)) {
            $stmt = $pdo->prepare("
                INSERT INTO survey_fields (survey_id, field_type, field_label, 
                                         field_options, is_required, sort_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($questions as $index => $question) {
                $stmt->execute([
                    $survey_id,
                    $question['field_type'],
                    $question['field_label'],
                    $question['field_options'],
                    $question['is_required'],
                    $index
                ]);
            }
        }

        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = $survey_id ? "Survey updated successfully!" : "Survey created successfully!";
        header("Location: surveys.php");
        exit();

    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Error saving survey: " . $e->getMessage());
        $_SESSION['error'] = "Failed to save survey: " . $e->getMessage();
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
                <form method="POST" class="survey-form">
                    <input type="hidden" name="id" value="<?= $survey_id ?? '' ?>">
                    
                    <div class="form-group">
                        <label for="title">Survey Title *</label>
                        <input type="text" id="title" name="title" 
                               value="<?= htmlspecialchars($survey['title'] ?? '') ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($survey['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Target Roles *</label>
                        <div class="roles-grid">
                            <?php foreach ($roles as $role): ?>
                                <div class="role-checkbox">
                                    <label>
                                        <input type="checkbox" name="target_roles[]" value="<?= $role['id'] ?>"
                                               <?= in_array($role['id'], $survey['target_roles'] ?? []) ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"
                                        <?= $category['id'] == ($survey['category_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['id'] ?>"
                                        <?= $status['id'] == ($survey['status'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="starts_at">Start Date *</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" 
                               value="<?= date('Y-m-d\TH:i', strtotime($survey['starts_at'] ?? '+1 day')) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="ends_at">End Date *</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" 
                               value="<?= date('Y-m-d\TH:i', strtotime($survey['ends_at'] ?? '+1 month')) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Options</label>
                        <div class="options">
                            <div class="form-check">
                                <input type="checkbox" id="is_anonymous" name="is_anonymous" 
                                       <?= isset($survey['is_anonymous']) ? 'checked' : '' ?>>
                                <label for="is_anonymous">Make survey anonymous</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="is_active" name="is_active" 
                                       <?= isset($survey['is_active']) ? 'checked' : '' ?>>
                                <label for="is_active">Activate survey</label>
                            </div>
                        </div>
                    </div>

                    <div id="questions-container">
                        <?php if (isset($survey['questions'])): ?>
                            <?php foreach ($survey['questions'] as $index => $question): ?>
                                <div class="question-box">
                                    <div class="question-header">
                                        <span>Question <?= $index + 1 ?></span>
                                        <button type="button" class="remove-question">Remove</button>
                                    </div>
                                    <div class="question-content">
                                        <input type="text" name="questions[]" 
                                               value="<?= htmlspecialchars($question['question']) ?>" 
                                               required>
                                        <select name="field_types[]" required>
                                            <option value="text" <?= $question['field_type'] == 'text' ? 'selected' : '' ?>>Text</option>
                                            <option value="textarea" <?= $question['field_type'] == 'textarea' ? 'selected' : '' ?>>Textarea</option>
                                            <option value="radio" <?= $question['field_type'] == 'radio' ? 'selected' : '' ?>>Radio</option>
                                            <option value="checkbox" <?= $question['field_type'] == 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
                                            <option value="select" <?= $question['field_type'] == 'select' ? 'selected' : '' ?>>Select</option>
                                        </select>
                                        <input type="checkbox" name="required[]" 
                                               <?= $question['required'] ? 'checked' : '' ?>>
                                        <label>Required</label>
                                        <textarea name="options[]" placeholder="Enter options separated by new line (for radio, checkbox, select)">
                                            <?= htmlspecialchars($question['options'] ?? '') ?>
                                        </textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="add-question">Add Question</button>
                    <button type="submit" class="btn-primary">Save Survey</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../assets/js/survey_builder.js"></script>
    <script>
        $(document).ready(function() {
            let questionIndex = <?= isset($survey['questions']) ? count($survey['questions']) : 0 ?>;

            // Add question button click handler
            $('.add-question').click(function() {
                const questionBox = `
                    <div class="question-box">
                        <div class="question-header">
                            <span>Question ${questionIndex + 1}</span>
                            <button type="button" class="remove-question">Remove</button>
                        </div>
                        <div class="question-content">
                            <input type="text" name="questions[]" required placeholder="Enter question text">
                            <select name="field_types[]" required>
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="radio">Radio</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="select">Select</option>
                            </select>
                            <input type="checkbox" name="required[]">
                            <label>Required</label>
                            <textarea name="options[]" placeholder="Enter options separated by new line (for radio, checkbox, select)"></textarea>
                        </div>
                    </div>
                `;

                $('#questions-container').append(questionBox);
                questionIndex++;
            });

            // Remove question button click handler
            $(document).on('click', '.remove-question', function() {
                $(this).closest('.question-box').remove();
            });
        });
    </script>
</body>
</html>
<?php include 'includes/footer.php'; ?>
