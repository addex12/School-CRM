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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?? null;
    $status_id = $_POST['status'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate and encode target_roles
    $target_roles = $_POST['target_roles'] ?? [];
    if (!is_array($target_roles)) {
        $target_roles = [];
    }
    if (!in_array($status_id, $target_roles)) {
        $target_roles[] = $status_id;
    }
    try {
        $pdo->beginTransaction();

        if ($survey_id) {
            // Update existing survey
            $survey = Survey::model()->findByPk($survey_id);
            $survey->status = $status_id;
            $survey->save();
            $stmt = $pdo->prepare("UPDATE surveys SET title = ?, description = ?, category_id = ?, status = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $description, $category_id, $status_id, $is_active, $survey_id]);

            // Update survey roles
            $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?")->execute([$survey_id]);
            foreach ($target_roles as $role_id) {
                $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)")->execute([$survey_id, $role_id]);
            }

            $_SESSION['success'] = "Survey updated successfully!";
        } else {
            // Create new survey
            $stmt = $pdo->prepare("INSERT INTO surveys (title, description, category_id, status, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category_id, $status_id, $is_active, $_SESSION['user_id']]);
            $survey_id = $pdo->lastInsertId();

            // Assign roles to the new survey
            foreach ($target_roles as $role_id) {
                $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)")->execute([$survey_id, $role_id]);
            }

            $_SESSION['success'] = "Survey created successfully!";
        }
        $stmt = $pdo->prepare("");
        $stmt->execute([$title, $description, $category_id, $status_id, $is_active, $_SESSION["user_id"]]);
        $_SESSION["success"] = "Survey saved successfully!";

        // Save survey questions
        if (isset($_POST['questions'])) {
            $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
            $stmt->execute([$title, $description, $category_id, $status_id, $is_active, $_SESSION["user_id"]]);
            $stmt = $pdo->prepare("INSERT INTO surveys (title, description, category_id, status, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$survey_id]);
            $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
            foreach ($target_roles as $role_id) {
                $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                $stmt->execute([$survey_id, $role_id]);
            }
            foreach ($target_roles as $role_id) {
                $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                $stmt->execute([$survey_id, $role_id]);
            }

            foreach ($_POST['questions'] as $index => $question) {
                $field_type = $_POST['field_types'][$index];
                $options = in_array($field_type, ['radio', 'checkbox', 'select']) ? $_POST['options'][$index] : null;
                $placeholder = $_POST['placeholders'][$index];
                $is_required = isset($_POST['required'][$index]) ? 1 : 0;

                $stmt = $pdo->prepare("INSERT INTO survey_fields (survey_id, field_type, field_label, field_options, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$survey_id, $field_type, $question, $options, $is_required, $index]);
            }
        }

        $pdo->commit();
        header("Location: surveys.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error saving survey: " . $e->getMessage());
        $_SESSION['error'] = "Failed to save survey. Please try again.";
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .roles-grid label {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .roles-grid input[type="checkbox"] {
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
                                <option value="<?= htmlspecialchars($category['id']) ?>" <?= $category['id'] == ($survey['category_id'] ?? '') ? 'selected' : '' ?> >
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
                                <label>
                                    <input type="checkbox" name="target_roles[]" value="<?= htmlspecialchars($role['id']) ?>" <?= in_array($role['id'], json_decode($survey['target_roles'] ?? '[]', true)) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
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
