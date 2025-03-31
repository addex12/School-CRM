<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Survey Builder";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category_id = intval($_POST['category_id']);
        $target_roles = json_encode($_POST['target_roles'] ?? []);
        $starts_at = $_POST['starts_at'];
        $ends_at = $_POST['ends_at'];
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($description)) {
            throw new Exception("Title and description are required.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO surveys (title, description, category_id, target_roles, created_by, created_at, starts_at, ends_at, is_anonymous, is_active, status) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'draft')
        ");
        $stmt->execute([
            $title, $description, $category_id, $target_roles, $_SESSION['user_id'], $starts_at, $ends_at, $is_anonymous, $is_active
        ]);

        $_SESSION['success'] = "Survey created successfully!";
        header("Location: surveys.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Fetch categories for the dropdown
$stmt = $pdo->query("SELECT * FROM survey_categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <section class="form-section">
                    <h2>Create New Survey</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="title">Survey Title</label>
                            <input type="text" name="title" id="title" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select name="category_id" id="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="target_roles">Target Roles</label>
                            <select name="target_roles[]" id="target_roles" multiple required>
                                <option value="admin">Admin</option>
                                <option value="teacher">Teacher</option>
                                <option value="student">Student</option>
                                <option value="parent">Parent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="starts_at">Start Date</label>
                            <input type="datetime-local" name="starts_at" id="starts_at" required>
                        </div>
                        <div class="form-group">
                            <label for="ends_at">End Date</label>
                            <input type="datetime-local" name="ends_at" id="ends_at" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_anonymous"> Anonymous Survey
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active"> Activate Survey
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Survey</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
