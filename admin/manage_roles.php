<?php
require_once '../includes/config.php';

// Fetch roles
$roles = [];
$result = $pdo->query("SELECT id, role_name FROM roles");
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $roles[] = $row;
    }
}

// Fetch all permissions
$permissions = [];
$res = $pdo->query("SELECT id, name, label FROM permissions ORDER BY label");
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $permissions[] = $row;
    }
}

// Fetch assigned permissions for each role
$role_permissions = [];
$res = $pdo->query("SELECT role_id, permission_id FROM role_permissions");
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $role_permissions[$row['role_id']][] = $row['permission_id'];
    }
}

// Handle permission update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'], $_POST['permissions'])) {
    $role_id = intval($_POST['role_id']);
    $perms = array_map('intval', $_POST['permissions']);

    // Remove all current permissions
    $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$role_id]);
    // Add selected permissions
    $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    foreach ($perms as $perm_id) {
        $stmt->execute([$role_id, $perm_id]);
    }
    header("Location: manage_roles.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Roles & Permissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* ERPNext-inspired card and table styling */
        body { font-family: Inter, Arial, sans-serif; background: #f5f7fa; margin: 0; }
        .container { margin-left: 270px; padding: 2rem; }
        h2 { color: #215967; margin-bottom: 1.5rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem;
            margin-bottom: 2rem;
        }
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
        .erp-btn i { margin-right: 6px; }
        .erp-btn:hover { background: #215967; }
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
        .actions button {
            background: #f3f4f6;
            border: none;
            color: #215967;
            border-radius: 4px;
            padding: 0.4rem 0.7rem;
            margin-right: 6px;
            cursor: pointer;
            transition: background 0.18s;
        }
        .actions button:hover { background: #e2efda; }
        .permissions-list label {
            display: inline-block;
            margin-right: 1.2rem;
            margin-bottom: 0.3rem;
            font-size: 0.97rem;
        }
        .permissions-list input[type="checkbox"] {
            accent-color: #2563eb;
            margin-right: 4px;
        }
        @media (max-width: 900px) {
            .container { margin-left: 70px; padding: 1rem; }
            .erp-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .container { margin-left: 0; padding: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 style="color:#215967;font-weight:700;"><i class="fas fa-user-shield"></i> Manage Roles</h1>
            </header>
            <div class="container">
                <div class="erp-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Permissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= htmlspecialchars($role['role_name']) ?></td>
                                <td>
                                    <form method="post" action="">
                                        <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                        <div class="permissions-list">
                                        <?php foreach ($permissions as $perm): ?>
                                            <label>
                                                <input type="checkbox" name="permissions[]" value="<?= $perm['id'] ?>"
                                                    <?= in_array($perm['id'], $role_permissions[$role['id']] ?? []) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($perm['label']) ?>
                                            </label>
                                        <?php endforeach; ?>
                                        </div>
                                        <button type="submit" class="erp-btn" style="padding:0.3rem 1rem;font-size:0.95rem;margin-top:0.5rem;">
                                            <i class="fas fa-save"></i>Save
                                        </button>
                                    </form>
                                </td>
                                <td class="actions">
                                    <button title="Edit"><i class="fas fa-edit"></i></button>
                                    <button title="Delete"><i class="fas fa-trash"></i></button>
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

</body>
</html>
