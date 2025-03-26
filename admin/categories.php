<?php
include 'includes/header.php';
require_once '../includes/config.php'; // Include config to initialize $pdo
require_once '../includes/auth.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $pdo->prepare("INSERT INTO survey_categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $_SESSION['success'] = "Category added successfully!";
    }
    
    if (isset($_POST['update_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $pdo->prepare("UPDATE survey_categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        $_SESSION['success'] = "Category updated successfully!";
    }
    
    if (isset($_POST['delete_category'])) {
        $id = $_POST['id'];
        
        // Check if category is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = "Cannot delete category that is in use by surveys!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM survey_categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Category deleted successfully!";
        }
    }
    
    header("Location: categories.php");
    exit();
}

// Get all categories
$categories = $pdo->query("SELECT * FROM survey_categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Survey Categories</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="surveys.php">Surveys</a>
                <a href="survey_builder.php">Survey Builder</a>
                <a href="categories.php" class="active">Categories</a>
                <a href="users.php">Users</a>
                <a href="results.php">Results</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <h2>Add New Category</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Category Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </form>
            </div>
            
            <div class="table-section">
                <h2>Existing Categories</h2>
                
                <?php if (count($categories) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-edit" onclick="openEditModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($category['description'], ENT_QUOTES); ?>')">Edit</button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this category?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No categories found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h2>Edit Category</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="update_category">
                
                <div class="form-group">
                    <label for="editName">Category Name:</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="editDescription">Description:</label>
                    <textarea id="editDescription" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Edit modal functions
        function openEditModal(id, name, description) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>