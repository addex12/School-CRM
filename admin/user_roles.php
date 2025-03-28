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
    <style>
        /* Add modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            width: 50%;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        .modal h2 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <!-- ... [Keep the existing header and content structure] ... -->

            <!-- Edit Role Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeEditModal()">&times;</span>
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
                // Modal control functions
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

                // Close modal on ESC key
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeEditModal();
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>