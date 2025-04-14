<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../models/Survey.php';

$pageTitle = "Survey Builder";
$survey_id = $_GET['id'] ?? null;
$survey = null;

// Fetch survey details if editing an existing survey
if ($survey_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, GROUP_CONCAT(DISTINCT r.role_name) as target_roles
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
            $questionStmt = $pdo->prepare("
                SELECT q.*, COALESCE(q.field_options, '') as field_options
                FROM survey_questions q
                WHERE q.survey_id = ?
                ORDER BY q.sort_order
            ");
            $questionStmt->execute([$survey_id]);
            $survey['questions'] = $questionStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching survey data: " . $e->getMessage());
        $_SESSION['error'] = "Failed to load survey data: " . $e->getMessage();
        header("Location: surveys.php");
        exit();
    }
}

// Fetch roles, categories, and statuses dynamically from the database
$roles = fetchAll($pdo, "SELECT id, role_name FROM roles ORDER BY role_name");
$categories = fetchAll($pdo, "SELECT id, name FROM survey_categories ORDER BY name");
$statuses = fetchAll($pdo, "SELECT id, status, label FROM survey_statuses ORDER BY id");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $target_roles = isset($_POST['target_roles']) ? array_map('intval', $_POST['target_roles']) : [];
    $questions = prepareQuestions($_POST['questions'], $_POST['field_types'], $_POST['options'], $_POST['required'] ?? []);

    try {
        $pdo->beginTransaction();
        if ($survey_id) {
            updateSurvey($pdo, $survey_data, $survey_id);
        } else {
            $survey_id = createSurvey($pdo, $survey_data);
        }

        updateTargetRoles($pdo, $survey_id, $target_roles);
        updateSurveyQuestions($pdo, $survey_id, $questions);
        $pdo->commit();

        $_SESSION['success'] = $survey_id ? "Survey updated successfully!" : "Survey created successfully!";
        header("Location: surveys.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error saving survey: " . $e->getMessage());
        $_SESSION['error'] = "Failed to save survey: " . $e->getMessage();
    }
}

function fetchAll($pdo, $query) {
    try {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching data: " . $e->getMessage());
        return [];
    }
}

function prepareQuestions($questions, $field_types, $options, $required) {
    $preparedQuestions = [];
    foreach ($questions as $index => $question) {
        $preparedQuestions[] = [
            'field_type' => $field_types[$index] ?? null,
            'field_label' => $question,
            'field_options' => $options[$index] ?? null,
            'is_required' => isset($required) && isset($required[$index]),
            'sort_order' => $index + 1
        ];
    }
    return $preparedQuestions;
}

function updateSurvey($pdo, $survey_data, $survey_id) {
    $stmt = $pdo->prepare("
        UPDATE surveys 
        SET title = ?, description = ?, category_id = ?, status = ?, 
            is_active = ?, is_anonymous = ?, starts_at = ?, ends_at = ?
        WHERE id = ?
    ");
    $stmt->execute(array_values(array_merge($survey_data, ['id' => $survey_id])));
}

function createSurvey($pdo, $survey_data) {
    $stmt = $pdo->prepare("
        INSERT INTO surveys 
        (title, description, category_id, status, is_active, is_anonymous, starts_at, ends_at, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->execute(array_merge(array_values($survey_data), [$_SESSION['user_id']]));
    return $pdo->lastInsertId();
}

function updateTargetRoles($pdo, $survey_id, $target_roles) {
    $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
    $stmt->execute([$survey_id]);

    if (!empty($target_roles)) {
        $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
        foreach ($target_roles as $role_id) {
            $stmt->execute([$survey_id, $role_id]);
        }
    }
}

function updateSurveyQuestions($pdo, $survey_id, $questions) {
    $stmt = $pdo->prepare("DELETE FROM survey_questions WHERE survey_id = ?");
    $stmt->execute([$survey_id]);

    if (!empty($questions)) {
        $stmt = $pdo->prepare("
            INSERT INTO survey_questions 
            (survey_id, field_type, field_label, field_options, is_required, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($questions as $question) {
            $stmt->execute([
                $survey_id,
                $question['field_type'],
                $question['field_label'],
                $question['field_options'],
                $question['is_required'],
                $question['sort_order']
            ]);
        }
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
        /* Add your custom styles here */
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
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title'] ?? '') ?>" required>
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
                                        <input type="checkbox" name="target_roles[]" value="<?= $role['id'] ?>" <?= in_array($role['id'], $survey['target_roles'] ?? []) ? 'checked' : '' ?>>
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
                                <option value="<?= $category['id'] ?>" <?= $category['id'] == ($survey['category_id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['id'] ?>" <?= $status['id'] == ($survey['status'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($status['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="starts_at">Start Date *</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="<?= date('Y-m-d\TH:i', strtotime($survey['starts_at'] ?? '+1 day')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="ends_at">End Date *</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="<?= date('Y-m-d\TH:i', strtotime($survey['ends_at'] ?? '+1 month')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_anonymous" <?= isset($survey['is_anonymous']) ? 'checked' : '' ?>> Make survey anonymous
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?= isset($survey['is_active']) ? 'checked' : '' ?>> Activate survey
                        </label>
                    </div>
                    <div id="questions-container">
                        <?php if (isset($survey['questions'])): ?>
                            <?php foreach ($survey['questions'] as $question): ?>
                                <div class="question-box">
                                    <div class="question-header">
                                        <span>Question <?= htmlspecialchars($question['sort_order']) ?></span>
                                        <button type="button" class="remove-question">Remove</button>
                                    </div>
                                    <div class="question-content">
                                        <input type="text" name="questions[]" value="<?= htmlspecialchars($question['field_label']) ?>" required>
                                        <select name="field_types[]" required>
                                            <option value="text" <?= $question['field_type'] == 'text' ? 'selected' : '' ?>>Text</option>
                                            <option value="textarea" <?= $question['field_type'] == 'textarea' ? 'selected' : '' ?>>Textarea</option>
                                            <option value="radio" <?= $question['field_type'] == 'radio' ? 'selected' : '' ?>>Radio</option>
                                            <option value="checkbox" <?= $question['field_type'] == 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
                                            <option value="select" <?= $question['field_type'] == 'select' ? 'selected' : '' ?>>Select</option>
                                        </select>
                                        <input type="checkbox" name="required[]" <?= $question['is_required'] ? 'checked' : '' ?>> Required
                                        <textarea name="options[]" placeholder="Enter options separated by new line (for radio, checkbox, select)"><?= htmlspecialchars($question['field_options'] ?? '') ?></textarea>
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

            $(document).on('click', '.remove-question', function() {
                $(this).closest('.question-box').remove();
            });
        });
    </script>
</body>
</html>
<?php include 'includes/footer.php'; ?>