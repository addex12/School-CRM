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

// Default: show all active users (online and offline) if no filters/search
if ($search !== '') {
    $where[] = "(u.username LIKE :search OR u.email LIKE :search)";
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
                        <button class="refresh-btn" type="button">
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
            <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Link the JS file for all row/bulk actions and AJAX -->
    <script src="../assets/js/active_users.js"></script>
    <script>
    // Ensure edit/delete and bulk actions work after AJAX table reload
    document.addEventListener('DOMContentLoaded', function() {
        function fetchUsersTable() {
            const search = document.getElementById('searchInput').value;
            const online = document.getElementById('onlineInput').checked ? 1 : '';
            const role = document.getElementById('roleInput').value;
            const status = document.getElementById('statusInput').value;
            const params = new URLSearchParams({
                ajax: 1,
                search: search,
                online: online,
                role: role,
                status: status
            });
            fetch('ajax_active_users.php?' + params.toString())
                .then(res => res.text())
                .then(html => {
                    document.getElementById('usersTableBody').innerHTML = html;
                    bindRowActions();
                });
        }

        function getSelectedUserIds() {
            return Array.from(document.querySelectorAll('.row-select:checked'))
                .map(cb => cb.closest('tr').getAttribute('data-id'));
        }

        function bindRowActions() {
            document.querySelectorAll('.crud-btn.edit').forEach(function(btn) {
                btn.onclick = function() {
                    var tr = btn.closest('tr');
                    if (!tr || tr.classList.contains('editing')) return;
                    tr.classList.add('editing');
                    var usernameTd = tr.querySelector('.username');
                    var roleTd = tr.querySelector('.role');
                    var statusTd = tr.querySelector('.status');
                    var actionsTd = btn.parentElement;
                    var currentUsername = usernameTd.textContent.trim();
                    var currentRoleId = roleTd.getAttribute('data-role-id');
                    var currentStatus = statusTd.getAttribute('data-status');
                    var roleOptions = '';
                    <?php foreach ($roles as $id => $name): ?>
                        roleOptions += '<option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>';
                    <?php endforeach; ?>
                    usernameTd.innerHTML = '<input type="text" value="' + currentUsername.replace(/"/g, '&quot;') + '" class="edit-username" style="width:120px;">';
                    roleTd.innerHTML = '<select class="edit-role">' + roleOptions + '</select>';
                    roleTd.querySelector('select').value = currentRoleId;
                    statusTd.innerHTML = '<select class="edit-status"><option value="1">Active</option><option value="0">Inactive</option></select>';
                    statusTd.querySelector('select').value = currentStatus;
                    actionsTd.innerHTML = '<button class="crud-btn save" type="button">Save</button> <button class="crud-btn cancel" type="button">Cancel</button>';

                    // Re-bind save/cancel after replacing innerHTML
                    actionsTd.querySelector('.save').addEventListener('click', function() {
                        var newUsername = usernameTd.querySelector('input').value.trim();
                        var newRoleId = roleTd.querySelector('select').value;
                        var newStatus = statusTd.querySelector('select').value;
                        var userId = tr.getAttribute('data-id');
                        var formData = new FormData();
                        formData.append('ajax', 'update_user');
                        formData.append('id', userId);
                        formData.append('username', newUsername);
                        formData.append('role_id', newRoleId);
                        formData.append('status', newStatus);
                        fetch('ajax_active_users.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.text())
                        .then(function(response) {
                            if (response.trim() === 'success') {
                                fetchUsersTable();
                            } else {
                                alert('Failed to update user.');
                            }
                        });
                    });
                    actionsTd.querySelector('.cancel').addEventListener('click', function() {
                        fetchUsersTable();
                    });
                };
            });
            document.querySelectorAll('.crud-btn.delete').forEach(function(btn) {
                btn.onclick = function() {
                    var tr = btn.closest('tr');
                    var userId = tr.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this user?')) {
                        var formData = new FormData();
                        formData.append('ajax', 'delete_user');
                        formData.append('id', userId);
                        fetch('ajax_active_users.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.text())
                        .then(function(response) {
                            if (response.trim() === 'success') {
                                fetchUsersTable();
                            } else {
                                alert('Failed to delete user.');
                            }
                        });
                    }
                };
            });
        }

        // Real-time search: trigger fetch on input, and prevent form submit from interfering
        const searchInput = document.getElementById('searchInput');
        const userSearchForm = document.getElementById('userSearchForm');

        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchUsersTable, 200); // debounce for smoother UX
        });

        userSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchUsersTable();
        });

        document.querySelector('.refresh-btn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default button action
            fetchUsersTable();  // Reload table via AJAX
        });

        document.getElementById('userSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetchUsersTable();
        });

        document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
            const ids = getSelectedUserIds();
            if (!ids.length) return alert('No users selected.');
            if (!confirm('Delete selected users?')) return;
            fetch('ajax_active_users.php', {
                method: 'POST',
                body: new URLSearchParams({ ajax: 'bulk_delete', ids: JSON.stringify(ids) })
            }).then(() => fetchUsersTable());
        });

        document.getElementById('bulkStatusSelect').addEventListener('change', function() {
            const status = this.value;
            if (status === '') return;
            const ids = getSelectedUserIds();
            if (!ids.length) return alert('No users selected.');
            fetch('ajax_active_users.php', {
                method: 'POST',
                body: new URLSearchParams({ ajax: 'bulk_status', ids: JSON.stringify(ids), status: status })
            }).then(() => fetchUsersTable());
            this.value = '';
        });

        document.getElementById('bulkRoleSelect').addEventListener('change', function() {
            const role_id = this.value;
            if (role_id === '') return;
            const ids = getSelectedUserIds();
            if (!ids.length) return alert('No users selected.');
            fetch('ajax_active_users.php', {
                method: 'POST',
                body: new URLSearchParams({ ajax: 'bulk_role', ids: JSON.stringify(ids), role_id: role_id })
            }).then(() => fetchUsersTable());
            this.value = '';
        });

        document.getElementById('bulkExportBtn').addEventListener('click', function() {
            const ids = getSelectedUserIds();
            const params = new URLSearchParams({
                ajax: 'bulk_export',
                ids: JSON.stringify(ids),
                status: document.getElementById('statusInput').value,
                role: document.getElementById('roleInput').value,
                online: document.getElementById('onlineInput').checked ? 1 : '',
                search: document.getElementById('searchInput').value
            });
            window.location = 'ajax_active_users.php?' + params.toString();
        });

        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        });

        // Initial binding for edit/delete
        bindRowActions();

        // On page load, show all active users (default)
        fetchUsersTable();
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
