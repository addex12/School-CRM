<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Survey Builder";

// Fetch survey details if editing an existing survey
$survey_id = $_GET['id'] ?? null;
$survey = null;
if ($survey_id) {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($survey_id) {
        // Update existing survey
        $stmt = $pdo->prepare("UPDATE surveys SET title = ?, description = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$title, $description, $is_active, $survey_id]);
        $_SESSION['success'] = "Survey updated successfully!";
    } else {
        // Create new survey
        $stmt = $pdo->prepare("INSERT INTO surveys (title, description, is_active, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $is_active, $_SESSION['user_id']]);
        $survey_id = $pdo->lastInsertId();
        $_SESSION['success'] = "Survey created successfully!";
    }

    // Save survey questions
    if (isset($_POST['questions'])) {
        $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
        $stmt->execute([$survey_id]);

        foreach ($_POST['questions'] as $index => $question) {
            $field_type = $_POST['field_types'][$index];
            $options = in_array($field_type, ['radio', 'checkbox', 'select']) ? $_POST['options'][$index] : null;
            $placeholder = $_POST['placeholders'][$index];
            $is_required = isset($_POST['required'][$index]) ? 1 : 0;

            $stmt = $pdo->prepare("INSERT INTO survey_fields (survey_id, field_type, field_label, field_options, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$survey_id, $field_type, $question, $options, $is_required, $index]);
        }
    }

    header("Location: surveys.php");
    exit();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .form-group textarea {
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
                        <label>
                            <input type="checkbox" name="is_active" <?= isset($survey['is_active']) && $survey['is_active'] ? 'checked' : '' ?>>
                            Active
                        </label>
                    </div>
                    <div id="questions-container">
                        <!-- Questions will be dynamically added here -->
                    </div>
                    <div class="form-actions">
                        <button type="button" id="add-question" class="btn-primary">Add Question</button>
                        <button type="submit" class="btn-primary">Save Survey</button>
                        <a href="surveys.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>

                <div class="ai-suggestions">
                    <h3>AI Suggestions</h3>
                    <button id="generate-ai-questions" class="btn-primary">Generate Questions</button>
                    <div id="ai-output"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/survey_builder.js"></script>
</body>
</html>
<?php include 'includes/footer.php'; ?>
