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
<html>
<head>
    <title>User Roles Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: Inter, Arial, sans-serif; background: #f5f7fa; margin: 0; }
        .container { margin-left: 270px; padding: 2rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem;
            margin-bottom: 2rem;
            max-width: 600px;
        }
        .erp-card h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .admin-header h1 {
            color: #215967;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
        }
        th, td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background: #f4f8fb;
            color: #215967;
            font-weight: 600;
        }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) { background: #f8fafc; }
        .actions button, .actions a {
            background: #f3f4f6;
            border: none;
            color: #215967;
            border-radius: 4px;
            padding: 0.4rem 0.7rem;
            margin-right: 6px;
            cursor: pointer;
            transition: background 0.18s;
            text-decoration: none;
        }
        .actions button:hover, .actions a:hover { background: #e2efda; }
        .erp-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s;
            margin-bottom: 1rem;
        }
        .erp-btn:hover { background: #215967; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { font-weight: 600; color: #215967; }
        .form-group input[type="text"] {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #f9fafb;
            font-size: 1rem;
        }
        .alert-success { background: #dcfce7; color: #27ae60; padding: 0.7rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-error { background: #fee2e2; color: #e74c3c; padding: 0.7rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        @media (max-width: 900px) {
            .container { margin-left: 70px; padding: 1rem; }
            .erp-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .container { margin-left: 0; padding: 0.5rem; }
        }
        .erp-btn i { margin-right: 6px; }
    </style>
</head>
<bod>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-user-tag"></i> User Roles</h1>
            </header>
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
</div>

</div>

</div>

</div>
</body>            

</html>

<?php include 'includes/footer.php'; ?>