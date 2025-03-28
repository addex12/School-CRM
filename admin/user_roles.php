<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Manage User Roles";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_role'])) {
            $roleName = trim($_POST['role_name']);
            $description = trim($_POST['description']);
            
            // Validate input
            if (empty($roleName)) {
                throw new Exception("Role name cannot be empty");
            }
            
            // Check for existing role
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ?");
            $stmt->execute([$roleName]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Role name already exists");
            }
            
            // Insert new role
            $stmt = $pdo->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
            $stmt->execute([$roleName, $description]);
            $_SESSION['success'] = "Role added successfully!";
        }
        
        if (isset($_POST['update_role'])) {
            $roleId = $_POST['role_id'];
            $roleName = trim($_POST['role_name']);
            $description = trim($_POST['description']);
            
            // Validate input
            if (empty($roleName)) {
                throw new Exception("Role name cannot be empty");
            }
            
            // Check for existing role excluding current
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ? AND id != ?");
            $stmt->execute([$roleName, $roleId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Role name already exists");
            }
            
            // Update role
            $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, description = ? WHERE id = ?");
            $stmt->execute([$roleName, $description, $roleId]);
            $_SESSION['success'] = "Role updated successfully!";
        }
        
        if (isset($_POST['delete_role'])) {
            $roleId = $_POST['role_id'];
            
            // Check if role is in use
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = (SELECT role_name FROM roles WHERE id = ?)");
            $stmt->execute([$roleId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete role assigned to users");
            }
            
            // Delete role
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);
            $_SESSION['success'] = "Role deleted successfully!";
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: user_roles.php");
    exit();
}

// Get all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
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
                <h1>Manage User Roles</h1>
            </header>
            <div class="content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message"><?= $_SESSION['success'] ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Add Role Form -->
                <div class="card mb-4">
                    <h2>Add New Role</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Role Name:</label>
                            <input type="text" name="role_name" required>
                        </div>
                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="description" rows="2"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add_role" class="btn btn-primary">Add Role</button>
                        </div>
                    </form>
                </div>

                <!-- Roles Table -->
                <div class="card">
                    <h2>Existing Roles</h2>
                    <?php if (count($roles) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($role['role_name']) ?></td>
                                        <td><?= htmlspecialchars($role['description']) ?></td>
                                        <td>
                                            <button class="btn btn-edit" onclick="openEditModal(
                                                <?= $role['id'] ?>,
                                                '<?= htmlspecialchars($role['role_name']) ?>',
                                                '<?= htmlspecialchars($role['description']) ?>'
                                            )">Edit</button>
                                            
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                                <button type="submit" name="delete_role" class="btn btn-delete" 
                                                    onclick="return confirm('Are you sure you want to delete this role?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No roles found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Role</h2>
            <form method="POST">
                <input type="hidden" name="role_id" id="editRoleId">
                <input type="hidden" name="update_role">
                
                <div class="form-group">
                    <label>Role Name:</label>
                    <input type="text" name="role_name" id="editRoleName" required>
                </div>
                
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" id="editDescription" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, description) {
            document.getElementById('editRoleId').value = id;
            document.getElementById('editRoleName').value = name;
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
                closeEditModal();
            }
        }
    </script>
</body>
</html>