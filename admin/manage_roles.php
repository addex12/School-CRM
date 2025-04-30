<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/

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
            max-width: 900px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.25em;
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
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-btn-sm { padding: 2px 7px; font-size: 0.93em; border-radius: 3px; }
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
        .adugna-permissions-list label {
            display: inline-block;
            margin-right: 1.2rem;
            margin-bottom: 0.3rem;
            font-size: 0.97rem;
        }
        .adugna-permissions-list input[type="checkbox"] {
            accent-color: #2563eb;
            margin-right: 4px;
        }
        .adugna-actions button {
            background: #f3f4f6;
            border: none;
            color: #215967;
            border-radius: 4px;
            padding: 0.4rem 0.7rem;
            margin-right: 6px;
            cursor: pointer;
            transition: background 0.18s;
        }
        .adugna-actions button:hover { background: #e2efda; }
        @media (max-width: 900px) {
            .adugna-main-content, .adugna-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content, .adugna-card { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-header-title { font-size: 1.05em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-user-shield"></i> Manage Roles & Permissions
            </div>
            <div class="adugna-card">
                <div class="adugna-table-responsive">
                    <table class="adugna-table">
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
                                        <div class="adugna-permissions-list">
                                        <?php foreach ($permissions as $perm): ?>
                                            <label>
                                                <input type="checkbox" name="permissions[]" value="<?= $perm['id'] ?>"
                                                    <?= in_array($perm['id'], $role_permissions[$role['id']] ?? []) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($perm['label']) ?>
                                            </label>
                                        <?php endforeach; ?>
                                        </div>
                                        <button type="submit" class="adugna-btn adugna-btn-sm" style="margin-top:0.5rem;">
                                            <i class="fas fa-save"></i> Save
                                        </button>
                                    </form>
                                </td>
                                <td class="adugna-actions">
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
