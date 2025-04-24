<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

header('Content-Type: text/html; charset=utf-8');

// Helper: fetch roles
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_KEY_PAIR);

// Helper: get role name
function getUserRoleName($roleId) {
    global $roles;
    return $roles[$roleId] ?? 'Unknown';
}

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['ajax'] ?? '';
    if ($action === 'update_user') {
        $id = (int)$_POST['id'];
        $username = trim($_POST['username']);
        $role_id = (int)$_POST['role_id'];
        $status = (int)$_POST['status'];
        $stmt = $pdo->prepare("UPDATE users SET username = :username, role_id = :role_id, active = :active WHERE id = :id");
        $ok = $stmt->execute([
            ':username' => $username,
            ':role_id' => $role_id,
            ':active' => $status,
            ':id' => $id
        ]);
        echo $ok ? 'success' : 'fail';
        exit;
    }
    if ($action === 'delete_user') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $ok = $stmt->execute([':id' => $id]);
        echo $ok ? 'success' : 'fail';
        exit;
    }
    if ($action === 'bulk_delete') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (is_array($ids) && count($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($in)");
            $ok = $stmt->execute($ids);
            echo $ok ? 'success' : 'fail';
        } else {
            echo 'fail';
        }
        exit;
    }
    if ($action === 'bulk_status') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $status = (int)$_POST['status'];
        if (is_array($ids) && count($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE id IN ($in)");
            $params = array_merge([$status], $ids);
            $ok = $stmt->execute($params);
            echo $ok ? 'success' : 'fail';
        } else {
            echo 'fail';
        }
        exit;
    }
    if ($action === 'bulk_role') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $role_id = (int)$_POST['role_id'];
        if (is_array($ids) && count($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id IN ($in)");
            $params = array_merge([$role_id], $ids);
            $ok = $stmt->execute($params);
            echo $ok ? 'success' : 'fail';
        } else {
            echo 'fail';
        }
        exit;
    }
    // Export: just output CSV for selected or filtered users
    if ($action === 'bulk_export') {
        $ids = json_decode($_GET['ids'] ?? '[]', true);
        $where = [];
        $params = [];
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $where[] = "u.id IN ($in)";
            $params = array_merge($params, $ids);
        }
        if (!empty($_GET['search'])) {
            $where[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = '%' . $_GET['search'] . '%';
            $params[] = '%' . $_GET['search'] . '%';
        }
        if (!empty($_GET['online'])) {
            $where[] = "u.online = 1";
        }
        if (!empty($_GET['role'])) {
            $where[] = "u.role_id = ?";
            $params[] = $_GET['role'];
        }
        if (isset($_GET['status']) && ($_GET['status'] === '0' || $_GET['status'] === '1')) {
            $where[] = "u.active = ?";
            $params[] = $_GET['status'];
        }
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.last_active, u.online, u.role_id, u.active FROM users u $where_sql ORDER BY u.username");
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_export.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Username', 'Email', 'Last Active', 'Online', 'Role', 'Status']);
        foreach ($users as $user) {
            fputcsv($out, [
                $user['id'],
                $user['username'],
                $user['email'],
                $user['last_active'],
                $user['online'] ? 'Online' : 'Offline',
                getUserRoleName($user['role_id']),
                $user['active'] ? 'Active' : 'Inactive'
            ]);
        }
        fclose($out);
        exit;
    }
    // Unknown action
    echo 'fail';
    exit;
}

// GET: List users for table body
$search = trim($_GET['search'] ?? '');
$filter_online = isset($_GET['online']) && $_GET['online'] === '1';
$role_id = trim($_GET['role'] ?? '');
$status_filter = isset($_GET['status']) && ($_GET['status'] === '0' || $_GET['status'] === '1') ? $_GET['status'] : '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_online) {
    $where[] = "u.online = 1";
}
if ($role_id !== '') {
    $where[] = "u.role_id = ?";
    $params[] = $role_id;
}
if ($status_filter !== '') {
    $where[] = "u.active = ?";
    $params[] = $status_filter;
}
if ($status_filter === '') {
    $where[] = "u.active = 1";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT u.id, u.username, u.last_active, u.online, u.role_id, u.active FROM users u $where_sql ORDER BY u.username");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) === 0): ?>
<tr>
    <td colspan="8" class="text-center">No active users found</td>
</tr>
<?php
else:
    foreach ($users as $user):
?>
<tr data-id="<?= htmlspecialchars($user['id']) ?>">
    <td class="select-col"><input type="checkbox" class="row-select"></td>
    <td><?= htmlspecialchars($user['id']) ?></td>
    <td class="username"><?= htmlspecialchars($user['username']) ?></td>
    <td><?= htmlspecialchars($user['last_active'] ?? '') ?></td>
    <td class="online">
        <?php if (!empty($user['online'])): ?>
            <span class="online-dot"></span> <span style="color:#27ae60;font-weight:500;">Online</span>
        <?php else: ?>
            <span style="color:#aaa;">Offline</span>
        <?php endif; ?>
    </td>
    <td class="role" data-role-id="<?= htmlspecialchars($user['role_id'] ?? '') ?>"><?= htmlspecialchars(getUserRoleName($user['role_id'])) ?></td>
    <td class="status" data-status="<?= (int)$user['active'] ?>">
        <?= ((int)$user['active'] === 1 ? 'Active' : 'Inactive') ?>
    </td>
    <td>
        <button class="crud-btn edit">Edit</button>
        <button class="crud-btn delete">Delete</button>
    </td>
</tr>
<?php
    endforeach;
endif;
