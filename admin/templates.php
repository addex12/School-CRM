<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Templates";

// Fetch all templates
$stmt = $pdo->query("SELECT * FROM templates ORDER BY created_at DESC");
$templates = $stmt->fetchAll();

// Handle form submission for adding a new template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_template'])) {
    try {
        $name = trim($_POST['name']);
        $content = trim($_POST['content']);

        if (empty($name)) {
            throw new Exception("Template name is required.");
        }

        $stmt = $pdo->prepare("INSERT INTO templates (name, content, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $content]);

        $_SESSION['success'] = "Template added successfully!";
        header("Location: templates.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Handle template deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_template'])) {
    try {
        $template_id = intval($_POST['template_id']);
        $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
        $stmt->execute([$template_id]);

        $_SESSION['success'] = "Template deleted successfully!";
        header("Location: templates.php");
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
                    <h2>Add New Template</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Template Name</label>
                            <input type="text" name="name" id="name" required>
                        </div>
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea name="content" id="content" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="add_template" class="btn btn-primary">Add Template</button>
                    </form>
                </section>

                <section class="table-section">
                    <h2>Existing Templates</h2>
                    <?php if (count($templates) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($template['id']) ?></td>
                                        <td><?= htmlspecialchars($template['name']) ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($template['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                                <button type="submit" name="delete_template" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this template?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No templates found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
