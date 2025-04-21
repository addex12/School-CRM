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

$pageTitle = "Manage User Roles";

// Handle add/edit/delete role actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_role'])) {
            $role_name = trim($_POST['role_name']);
            if (empty($role_name)) throw new Exception("Role name is required.");
            $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
            $stmt->execute([$role_name]);
            $_SESSION['success'] = "Role added successfully!";
            header("Location: user_roles.php");
            exit();
        }
        if (isset($_POST['edit_role'])) {
            $role_id = intval($_POST['role_id']);
            $role_name = trim($_POST['role_name']);
            if (empty($role_name)) throw new Exception("Role name is required.");
            $stmt = $pdo->prepare("UPDATE roles SET role_name = ? WHERE id = ?");
            $stmt->execute([$role_name, $role_id]);
            $_SESSION['success'] = "Role updated successfully!";
            header("Location: user_roles.php");
            exit();
        }
        if (isset($_POST['delete_role'])) {
            $role_id = intval($_POST['role_id']);
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$role_id]);
            $_SESSION['success'] = "Role deleted successfully!";
            header("Location: user_roles.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Fetch all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .roles-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            margin: 2rem 0;
        }
        .roles-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .roles-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .roles-table {
            width: 100%;
            border-collapse: collapse;
        }
        .roles-table th, .roles-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .roles-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .roles-table tr:hover {
            background: #f4f8fb;
        }
        .role-actions button {
            margin-right: 8px;
        }
        @media (max-width: 900px) {
            .roles-container {
                padding: 1rem 0.5rem;
            }
            .roles-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .roles-table th, .roles-table td {
                padding: 8px 6px;
            }
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
            <div class="content">
                <div class="roles-container">
                    <div class="roles-header">
                        <h2>User Roles</h2>
                        <form method="POST" style="display:flex;gap:10px;">
                            <input type="text" name="role_name" placeholder="New role name" required>
                            <button type="submit" name="add_role" class="btn btn-primary">Add Role</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="roles-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Role Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($roles)): ?>
                                    <?php foreach ($roles as $role): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($role['id']) ?></td>
                                            <td><?= htmlspecialchars($role['role_name']) ?></td>
                                            <td class="role-actions">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                                    <input type="text" name="role_name" value="<?= htmlspecialchars($role['role_name']) ?>" required style="width:120px;">
                                                    <button type="submit" name="edit_role" class="btn btn-secondary btn-sm">Edit</button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                                    <button type="submit" name="delete_role" class="btn btn-danger btn-sm" onclick="return confirm('Delete this role?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No roles found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>