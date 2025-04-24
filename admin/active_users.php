<?php
ob_start();
// Error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Include required files
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Users";

// Handle search/filter
$search = trim($_GET['search'] ?? '');
$filter_online = isset($_GET['online']) && $_GET['online'] === '1';
$role_id = trim($_GET['role'] ?? '');
// Add status filter
$status_filter = isset($_GET['status']) && ($_GET['status'] === '0' || $_GET['status'] === '1') ? $_GET['status'] : '';

// Only filter by active if status filter is set, otherwise show all
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "u.username LIKE :search";
    $params[':search'] = "%$search%";
}
if ($filter_online) {
    $where[] = "u.online = 1";
}
if ($role_id !== '') {
    $where[] = "u.role_id = :role_id";
    $params[':role_id'] = $role_id;
}
if ($status_filter !== '') {
    $where[] = "u.active = :status";
    $params[':status'] = $status_filter;
}

// If no status filter, show only active users by default
if ($status_filter === '') {
    $where[] = "u.active = 1";
}

$where_sql = implode(' AND ', $where);

try {
    // Fix: Use correct role name column for both main and AJAX queries
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.last_active, u.online, u.role_id, u.active, r.role_name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE $where_sql
        ORDER BY u.username");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Split users into online and offline for display
    $online_users = [];
    $offline_users = [];
    foreach ($users as $user) {
        if (!empty($user['online'])) {
            $online_users[] = $user;
        } else {
            $offline_users[] = $user;
        }
    }

    // For stats: count active and online users from the FULL users table, not filtered
    $total_active = $pdo->query("SELECT COUNT(*) FROM users WHERE active = 1")->fetchColumn();
    $total_online = $pdo->query("SELECT COUNT(*) FROM users WHERE active = 1 AND online = 1")->fetchColumn();

    unset($error);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "A database error occurred. Please try again later.";
    $users = [];
    $total_active = 0;
    $total_online = 0;
}

// Fetch roles for filter dropdown (id => name)
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_KEY_PAIR);

// Helper for role name (for offline rendering)
function getUserRoleName($roleId) {
    global $roles;
    return $roles[$roleId] ?? 'Unknown';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/active_user.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="active-users-container">
                <div class="active-users-header">
                    <h2 style="font-size:1.45em; color:#1976d2; font-weight:600;">Active Users</h2>
                    <div>
                        <button class="refresh-btn" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <span class="active-count">
                            <i class="fas fa-users"></i>
                            <?= (int)$total_active ?> active,
                            <span style="color:#27ae60;"><i class="fas fa-circle"></i> <?= (int)$total_online ?> online</span>
                        </span>
                    </div>
                </div>
                <?php if (!empty($error)): ?>
                    <div style="color: red; margin-bottom: 1em;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Bulk Actions Bar -->
                <div class="bulk-actions-bar" id="bulkActionsBar" style="display:none;">
                    <span id="selectedCount">0 selected</span>
                    <button type="button" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
                    <select id="bulkStatusSelect">
                        <option value="">Set Status...</option>
                        <option value="1">Set Active</option>
                        <option value="0">Set Inactive</option>
                    </select>
                    <select id="bulkRoleSelect">
                        <option value="">Set Role...</option>
                        <?php foreach ($roles as $id => $name): ?>
                            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="bulkExportBtn"><i class="fas fa-download"></i> Export</button>
                </div>

                <form class="erpnext-search-form" id="userSearchForm" method="get" action="">
                    <input type="text" name="search" id="searchInput" placeholder="Search username..." value="<?= htmlspecialchars($search) ?>">
                    <label>
                        <input type="checkbox" name="online" id="onlineInput" value="1" <?= $filter_online ? 'checked' : '' ?>>
                        Online only
                    </label>
                    <select name="role" id="roleInput">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $id => $name): ?>
                            <option value="<?= htmlspecialchars($id) ?>" <?= (isset($_GET['role']) && $_GET['role'] == $id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Status filter dropdown -->
                    <select name="status" id="statusInput">
                        <option value="">All Statuses</option>
                        <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                </form>

                <table class="users-table" id="allUsersTable">
                    <thead>
                        <tr>
                            <th class="select-col"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Last Active</th>
                            <th>Online</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
<?php
$has_users = (isset($online_users) && count($online_users) > 0) || (isset($offline_users) && count($offline_users) > 0);
if ($has_users):
    $all_users = array_merge($online_users, $offline_users);
    foreach ($all_users as $user):
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
    <td class="role" data-role-id="<?= htmlspecialchars($user['role_id'] ?? '') ?>"><?= htmlspecialchars($user['role_name'] ?? getUserRoleName($user['role_id'])) ?></td>
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
else:
?>
<tr>
    <td colspan="8" class="text-center">No active users found</td>
</tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
    <!-- Link the JS file for all row/bulk actions and AJAX -->
    <script src="../assets/js/active_users.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
