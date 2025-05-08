<?php
// Start session at the very top for session reliability
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/

// Start output buffering and error reporting
ob_start();
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
    <!-- Developer: Adugna Gizaw | Responsive, compact, ERPNext-inspired admin user page -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/active_user.css">
    <style>
    /**
     * Developer: Adugna Gizaw
     * Custom adugna- styles for compact, ERPNext-inspired, responsive UI.
     * Sidebar/footer styles are not touched.
     */
    .adugna-main {
        padding: 0;
        margin: 0;
        min-height: 100vh;
        background: #f7f9fb;
        display: flex;
        flex-direction: column;
    }
    .adugna-active-users-container {
        max-width: 1100px;
        margin: 32px auto 0 auto;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
        padding: 18px 18px 28px 18px;
        transition: box-shadow 0.2s;
    }
    .adugna-active-users-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }
    .adugna-active-users-header h2 {
        font-size: 1.25em;
        color: #1976d2;
        font-weight: 700;
        margin: 0;
        letter-spacing: 0.01em;
    }
    .adugna-active-count {
        font-size: 0.98em;
        color: #444;
        margin-left: 12px;
    }
    .adugna-active-count i {
        font-size: 1em;
        margin-right: 2px;
    }
    .adugna-refresh-btn {
        background: #e3eafc;
        color: #1976d2;
        border: none;
        border-radius: 4px;
        padding: 4px 10px;
        font-size: 0.95em;
        margin-right: 8px;
        cursor: pointer;
        transition: background 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .adugna-refresh-btn i {
        font-size: 1em;
    }
    .adugna-refresh-btn:hover {
        background: #d0e2fa;
    }
    .adugna-bulk-actions-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f5f7fa;
        border-radius: 6px;
        padding: 6px 10px;
        margin-bottom: 12px;
        font-size: 0.97em;
        box-shadow: 0 1px 4px rgba(25,118,210,0.04);
    }
    .adugna-bulk-actions-bar button,
    .adugna-bulk-actions-bar select {
        font-size: 0.97em;
        padding: 3px 8px;
        border-radius: 4px;
        border: 1px solid #e0e0e0;
        background: #fff;
        color: #1976d2;
        margin-right: 2px;
        transition: background 0.15s;
    }
    .adugna-bulk-actions-bar button {
        background: #e3eafc;
        border: none;
        color: #1976d2;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .adugna-bulk-actions-bar button:hover {
        background: #d0e2fa;
    }
    .adugna-bulk-actions-bar i {
        font-size: 0.95em;
    }
    .adugna-erpnext-search-form {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
        align-items: center;
    }
    .adugna-erpnext-search-form input[type="text"] {
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #d0d7de;
        font-size: 0.97em;
        width: 170px;
    }
    .adugna-erpnext-search-form label {
        font-size: 0.97em;
        color: #555;
        margin-right: 8px;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .adugna-erpnext-search-form select {
        padding: 3px 8px;
        border-radius: 4px;
        border: 1px solid #d0d7de;
        font-size: 0.97em;
        background: #fff;
        color: #1976d2;
    }
    .adugna-search-btn {
        background: #1976d2;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 4px 12px;
        font-size: 0.97em;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: background 0.15s;
    }
    .adugna-search-btn i {
        font-size: 1em;
    }
    .adugna-search-btn:hover {
        background: #145ea8;
    }
    .adugna-users-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        font-size: 0.97em;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(25,118,210,0.04);
    }
    .adugna-users-table th, .adugna-users-table td {
        padding: 7px 8px;
        border-bottom: 1px solid #f0f0f0;
        text-align: left;
        vertical-align: middle;
    }
    .adugna-users-table th {
        background: #f5f7fa;
        color: #1976d2;
        font-weight: 600;
        font-size: 0.98em;
    }
    .adugna-users-table td {
        color: #333;
    }
    .adugna-users-table .select-col {
        width: 32px;
        text-align: center;
    }
    .adugna-users-table .crud-btn {
        background: #e3eafc;
        color: #1976d2;
        border: none;
        border-radius: 4px;
        padding: 2px 7px;
        font-size: 0.93em;
        margin-right: 2px;
        cursor: pointer;
        transition: background 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 2px;
    }
    .adugna-users-table .crud-btn i {
        font-size: 0.93em;
    }
    .adugna-users-table .crud-btn:hover {
        background: #d0e2fa;
    }
    .adugna-users-table .crud-btn.delete {
        color: #e74c3c;
        background: #fbeaea;
    }
    .adugna-users-table .crud-btn.delete:hover {
        background: #f8d7da;
    }
    @media (max-width: 900px) {
        .adugna-active-users-container {
            margin: 12px 2vw 0 2vw;
            padding: 10px 4px 18px 4px;
        }
        .adugna-users-table th, .adugna-users-table td {
            padding: 5px 4px;
            font-size: 0.95em;
        }
        .adugna-active-users-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .adugna-erpnext-search-form {
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
        }
    }
    </style>
</head>
<body>
    <!-- Main admin dashboard layout -->
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-active-users-container">
                <!-- Header: title, refresh, stats -->
                <div class="adugna-active-users-header">
                    <h2>Active Users</h2>
                    <div>
                        <button class="adugna-refresh-btn" type="button">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <span class="adugna-active-count">
                            <i class="fas fa-users"></i>
                            <?= (int)$total_active ?> active,
                            <span style="color:#27ae60;"><i class="fas fa-circle"></i> <?= (int)$total_online ?> online</span>
                        </span>
                    </div>
                </div>
                <?php if (!empty($error)): ?>
                    <!-- Error message -->
                    <div style="color: red; margin-bottom: 1em;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Bulk Actions Bar (hidden by default, shown when users selected) -->
                <div class="adugna-bulk-actions-bar" id="bulkActionsBar" style="display:none;">
                    <span id="selectedCount">0 selected</span>
                    <button type="button" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
                    <button type="button" id="bulkEditBtn"><i class="fas fa-edit"></i> Edit</button>
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
                    <button type="button" id="bulkExportBtn"><i class="fas fa-download"></i></button>
                </div>

                <!-- Search/filter form -->
                <form class="adugna-erpnext-search-form" id="userSearchForm" method="get" action="">
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
                    <select name="status" id="statusInput">
                        <option value="">All Statuses</option>
                        <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button type="submit" class="adugna-search-btn"><i class="fas fa-search"></i></button>
                </form>

                <!-- Users table -->
                <table class="adugna-users-table" id="allUsersTable">
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
                        <!-- AJAX-loaded user rows -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JS for all row/bulk actions and AJAX table -->
    <script src="../assets/js/active_users.js"></script>
    <script>
    /**
     * Developer: Adugna Gizaw
     * Interactive logic for user table: AJAX, inline edit, bulk actions, and responsive UI.
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch and render users table via AJAX
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

        // Get selected user IDs for bulk actions
        function getSelectedUserIds() {
            return Array.from(document.querySelectorAll('.row-select:checked'))
                .map(cb => cb.closest('tr').getAttribute('data-id'));
        }

        // Bind edit/delete actions for each row
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

                    // Save/cancel logic for inline edit
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

        // Real-time search: debounce input for smoother UX
        const searchInput = document.getElementById('searchInput');
        const userSearchForm = document.getElementById('userSearchForm');
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchUsersTable, 200);
        });

        // Prevent default form submit, use AJAX
        userSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchUsersTable();
        });

        // Refresh button reloads table via AJAX
        document.querySelector('.adugna-refresh-btn').addEventListener('click', function(e) {
            e.preventDefault();
            fetchUsersTable();
        });

        // Bulk actions: delete, status, role, export
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

        // Select all checkbox logic
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        });

        // Show/hide bulk actions bar based on selection
        function updateBulkActionsBar() {
            const selected = getSelectedUserIds();
            const bar = document.getElementById('bulkActionsBar');
            document.getElementById('selectedCount').textContent = selected.length + ' selected';
            bar.style.display = selected.length > 0 ? '' : 'none';
        }
        document.getElementById('allUsersTable').addEventListener('change', function(e) {
            if (e.target.classList.contains('row-select') || e.target.id === 'selectAll') {
                updateBulkActionsBar();
            }
        });
        // Bulk Edit: Open modal or inline edit for selected users
        document.getElementById('bulkEditBtn').addEventListener('click', function() {
            const ids = getSelectedUserIds();
            if (!ids.length) return alert('No users selected.');
            // For demo: prompt for new username, role, status (in real app, use a modal)
            const newUsername = prompt('Enter new username for all selected (leave blank to skip):');
            const newRoleId = prompt('Enter new role ID for all selected (leave blank to skip):');
            const newStatus = prompt('Enter new status (1=Active, 0=Inactive, leave blank to skip):');
            if (!newUsername && !newRoleId && !newStatus) return;
            const formData = new FormData();
            formData.append('ajax', 'bulk_edit');
            formData.append('ids', JSON.stringify(ids));
            if (newUsername) formData.append('username', newUsername);
            if (newRoleId) formData.append('role_id', newRoleId);
            if (newStatus) formData.append('status', newStatus);
            fetch('ajax_active_users.php', {
                method: 'POST',
                body: formData
            }).then(res => res.text()).then(function(response) {
                if (response.trim() === 'success') {
                    fetchUsersTable();
                } else {
                    alert('Bulk edit failed.');
                }
            });
        });

        // Initial binding and fetch
        bindRowActions();
        fetchUsersTable();
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
