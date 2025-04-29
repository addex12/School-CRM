<?php
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
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <section class="form-section card">
                    <h2>Add New Category</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Category Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus fa-sm"></i> Add Category
                        </button>
                    </form>
                </section>

                <section class="table-section card">
                    <h2>Existing Categories</h2>
                    <?php if (count($categories) > 0): ?>
                        <div class="table-responsive">
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
                                                <button class="btn btn-secondary btn-sm" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', '<?= htmlspecialchars($category['description']) ?>')">
                                                    <i class="fa fa-edit fa-sm"></i> Edit
                                                </button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                    <button type="submit" name="delete_category" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?')">
                                                        <i class="fa fa-trash fa-sm"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No categories found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Category</h2>
            <form method="POST">
                <input type="hidden" name="category_id" id="editCategoryId">
                <div class="form-group">
                    <label for="editName">Category Name</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" name="edit_category" class="btn btn-primary btn-sm">
                    <i class="fa fa-save fa-sm"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        function editCategory(id, name, description) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>

    <style>
        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 11px;
        }

        .fa-sm {
            font-size: 0.875em;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .modal {
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

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 15px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 6px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        @media (max-width: 900px) {
            .form-section, .table-section {
                padding: 10px !important;
                margin: 8px 0 !important;
            }
        }

        @media (max-width: 600px) {
            .form-section, .table-section {
                padding: 6px !important;
                margin: 4px 0 !important;
            }
            .form-actions {
                flex-direction: column !important;
                gap: 8px !important;
            }
            .admin-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 6px !important;
            }
            .page-title, h1, h2 {
                font-size: 1rem !important;
            }
            .table-responsive, .table {
                display: block;
                width: 100%;
                overflow-x: auto;
            }
            th, td {
                white-space: nowrap;
                font-size: 0.85em;
            }
            .modal-content {
                width: 90% !important;
                min-width: 0 !important;
            }
        }
    </style>
    <?php include 'includes/footer.php'; ?>
</body>
</html>