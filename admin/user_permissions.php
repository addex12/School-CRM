<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "User Permissions";

// Fetch users and their roles
$users = [];
$sql = "SELECT users.id, users.username, roles.id AS role_id, roles.role_name AS role_name
        FROM users
        LEFT JOIN roles ON users.role_id = roles.id";
$result = $pdo->query($sql);
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $users[] = [
            'id' => $row['id'],
            'name' => $row['username'],
            'role_id' => $row['role_id'],
            'role' => $row['role_name']
        ];
    }
}

// Fetch all roles
$roles = [];
$res = $pdo->query("SELECT id, role_name FROM roles");
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $roles[] = $row;
    }
}

// Fetch permissions for each role
$role_permissions = [];
$res = $pdo->query("SELECT role_id, permissions.label FROM role_permissions JOIN permissions ON role_permissions.permission_id = permissions.id");
if ($res) {
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $role_permissions[$row['role_id']][] = $row['label'];
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id'])) {
    $user_id = intval($_POST['user_id']);
    $role_id = intval($_POST['role_id']);
    $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->execute([$role_id, $user_id]);
    header("Location: user_permissions.php");
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
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Permissions</th>
                                    <th>Change Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['role'] ?? '') ?></td>
                                    <td>
                                        <?php
                                        $perms = $role_permissions[$user['role_id']] ?? [];
                                        echo $perms ? implode(', ', array_map('htmlspecialchars', $perms)) : '<span style="color:#aaa;">No permissions</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <form method="post" action="">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="role_id">
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?= $role['id'] ?>"<?= $role['id'] == $user['role_id'] ? ' selected' : '' ?>>
                                                        <?= htmlspecialchars($role['role_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit"><i class="fas fa-save"></i> Update</button>
                                        </form>
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
