<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Set timezone for categories management
date_default_timezone_set('Africa/Nairobi');

require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Categories";

// Fetch all categories
try {
    $stmt = $pdo->query("SELECT * FROM survey_categories ORDER BY created_at DESC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to fetch categories: " . $e->getMessage();
    $categories = [];
}

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

// Handle category editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    try {
        $category_id = intval($_POST['category_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            throw new Exception("Category name is required.");
        }

        $stmt = $pdo->prepare("UPDATE survey_categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $category_id]);

        $_SESSION['success'] = "Category updated successfully!";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            max-width: 1000px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header {
            font-size: 1.13em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 1em;
            letter-spacing: 0.01em;
        }
        .adugna-form-group {
            margin-bottom: 1rem;
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
        .adugna-form-group textarea {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-group textarea {
            min-height: 70px;
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
        .adugna-btn i {
            font-size: 1em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: #145ea8;
        }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover {
            background: #d0e2fa;
        }
        .adugna-btn-danger {
            background: #dc3545;
            color: #fff;
            border: 1px solid #dc3545;
        }
        .adugna-btn-danger:hover {
            background: #c82333;
        }
        .adugna-btn-sm {
            padding: 2px 7px;
            font-size: 0.93em;
            border-radius: 3px;
        }
        .adugna-table-responsive {
            overflow-x: auto;
            margin-top: 1em;
        }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.97em;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-table th, .adugna-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 600;
            font-size: 0.98em;
        }
        .adugna-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .adugna-table-actions {
            display: flex;
            gap: 0.5em;
        }
        .adugna-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .adugna-modal-content {
            background-color: #fefefe;
            margin: 8% auto;
            padding: 15px;
            border: 1px solid #888;
            width: 90%;
            max-width: 420px;
            border-radius: 8px;
        }
        .adugna-modal-close {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
        }
        .adugna-modal-close:hover,
        .adugna-modal-close:focus {
            color: #1976d2;
            text-decoration: none;
            cursor: pointer;
        }
        @media (max-width: 1100px) {
            .adugna-main-content { max-width: 99vw; margin: 18px 2vw 0 2vw; padding: 10px 4px 18px 4px; }
        }
        @media (max-width: 900px) {
            .adugna-main-content, .adugna-card { padding: 0.7rem 0.5rem 1rem 0.5rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content, .adugna-card { padding: 0.5rem 0.2rem 0.7rem 0.2rem; }
            .adugna-header-title { font-size: 1.1em; }
            .adugna-modal-content { width: 98% !important; min-width: 0 !important; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <header class="adugna-header-title">
                <i class="fas fa-layer-group"></i> <?= htmlspecialchars($pageTitle) ?>
            </header>
            <?php include 'includes/alerts.php'; ?>

            <!-- Adugna Gizaw: Add New Category Form -->
            <div class="adugna-card">
                <div class="adugna-card-header"><i class="fas fa-plus"></i> Add New Category</div>
                <form method="POST">
                    <div class="adugna-form-group">
                        <label for="name">Category Name</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="adugna-form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_category" class="adugna-btn adugna-btn-sm">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </form>
            </div>

            <!-- Adugna Gizaw: Existing Categories Table -->
            <div class="adugna-card">
                <div class="adugna-card-header"><i class="fas fa-list"></i> Existing Categories</div>
                <?php if (count($categories) > 0): ?>
                    <div class="adugna-table-responsive">
                        <table class="adugna-table">
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
                                        <td class="adugna-table-actions">
                                            <button class="adugna-btn adugna-btn-secondary adugna-btn-sm" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['name'])) ?>', '<?= htmlspecialchars(addslashes($category['description'])) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                <button type="submit" name="delete_category" class="adugna-btn adugna-btn-danger adugna-btn-sm" onclick="return confirm('Are you sure you want to delete this category?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:#888;">No categories found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Adugna Gizaw: Edit Modal for Category -->
    <div id="adugna-edit-modal" class="adugna-modal">
        <div class="adugna-modal-content">
            <span class="adugna-modal-close" onclick="closeModal()">&times;</span>
            <h2 style="margin-bottom:1em;"><i class="fas fa-edit"></i> Edit Category</h2>
            <form method="POST">
                <input type="hidden" name="category_id" id="adugna-editCategoryId">
                <div class="adugna-form-group">
                    <label for="adugna-editName">Category Name</label>
                    <input type="text" name="name" id="adugna-editName" required>
                </div>
                <div class="adugna-form-group">
                    <label for="adugna-editDescription">Description</label>
                    <textarea name="description" id="adugna-editDescription" rows="3"></textarea>
                </div>
                <button type="submit" name="edit_category" class="adugna-btn adugna-btn-sm">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        /**
         * Adugna Gizaw: Show modal for editing category, fill with selected values.
         */
        function editCategory(id, name, description) {
            document.getElementById('adugna-editCategoryId').value = id;
            document.getElementById('adugna-editName').value = name;
            document.getElementById('adugna-editDescription').value = description;
            document.getElementById('adugna-edit-modal').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('adugna-edit-modal').style.display = 'none';
        }
        // Optional: close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('adugna-edit-modal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>