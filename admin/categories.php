<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Categories";

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM survey_categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll();

// Handle form submission for adding a new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            throw new Exception("Category name is required.");
        }

        $stmt = $pdo->prepare("INSERT INTO survey_categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);

        $_SESSION['success'] = "Category added successfully!";
        header("Location: categories.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    try {
        $category_id = intval($_POST['category_id']);
        $stmt = $pdo->prepare("DELETE FROM survey_categories WHERE id = ?");
        $stmt->execute([$category_id]);

        $_SESSION['success'] = "Category deleted successfully!";
        header("Location: categories.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
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
                    <h2>Add New Category</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Category Name</label>
                            <input type="text" name="name" id="name" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </form>
                </section>

                <section class="table-section">
                    <h2>Existing Categories</h2>
                    <?php if (count($categories) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category['id']) ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= htmlspecialchars($category['description'] ?? 'N/A') ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($category['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                <button type="submit" name="delete_category" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No categories found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>