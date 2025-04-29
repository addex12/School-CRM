<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "User Roles";

// Handle add role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $role_name = trim($_POST['role_name']);
    if ($role_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
        try {
            $stmt->execute([$role_name]);
            $_SESSION['success'] = "Role added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Role name cannot be empty.";
    }
    header("Location: user_roles.php");
    exit;
}

// Fetch all roles
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);

// Handle delete role
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $role_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    try {
        $stmt->execute([$role_id]);
        $_SESSION['success'] = "Role deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: user_roles.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <?= htmlspecialchars($pageTitle) ?>
            </header>
            <div class="admin-content">
                <div class="container">
                    <div class="erp-card">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <form method="post" style="margin-bottom:2rem;">
                            <div class="form-group">
                                <label for="role_name">Add New Role</label>
                                <input type="text" name="role_name" id="role_name" required placeholder="Enter role name">
                            </div>
                            <button type="submit" name="add_role" class="erp-btn"><i class="fas fa-plus"></i> Add Role</button>
                        </form>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Role Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><?= htmlspecialchars($role['id']) ?></td>
                                    <td><?= htmlspecialchars($role['role_name']) ?></td>
                                    <td class="actions">
                                        <a href="?delete=<?= $role['id'] ?>" onclick="return confirm('Delete this role?');" title="Delete"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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

