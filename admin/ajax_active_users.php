<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Fetch roles for dropdown (for edit rendering)
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_KEY_PAIR);

// AJAX: Search/filter users
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $search = trim($_GET['search'] ?? '');
    $filter_online = isset($_GET['online']) && $_GET['online'] === '1';
    $role_id = trim($_GET['role'] ?? '');
    $status_filter = isset($_GET['status']) && ($_GET['status'] === '0' || $_GET['status'] === '1') ? $_GET['status'] : '';

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
    if ($status_filter === '') {
        $where[] = "u.active = 1";
    }

    $where_sql = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT u.id, u.username, u.last_active, u.online, u.role_id, u.active, r.role_name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE $where_sql
        ORDER BY u.username");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $online_users = [];
    $offline_users = [];
    foreach ($users as $user) {
        if (!empty($user['online'])) {
            $online_users[] = $user;
        } else {
            $offline_users[] = $user;
        }
    }

    foreach ($users as &$user) {
        $user['active'] = (int)$user['active'];
    }

    
    $has_users = (count($online_users) > 0) || (count($offline_users) > 0);
    if ($has_users) {
        $all_users = array_merge($online_users, $offline_users);
        foreach ($all_users as $user) {
            echo '<tr data-id="' . htmlspecialchars($user['id']) . '">';
            echo '<td class="select-col"><input type="checkbox" class="row-select"></td>';
            echo '<td>' . htmlspecialchars($user['id']) . '</td>';
            echo '<td class="username">' . htmlspecialchars($user['username']) . '</td>';
            echo '<td>' . htmlspecialchars($user['last_active'] ?? '') . '</td>';
            echo '<td class="online">';
            if (!empty($user['online'])) {
                echo '<span class="online-dot"></span> <span style="color:#27ae60;font-weight:500;">Online</span>';
            } else {
                echo '<span style="color:#aaa;">Offline</span>';
            }
            echo '</td>';
            echo '<td class="role" data-role-id="' . htmlspecialchars($user['role_id'] ?? '') . '">' . htmlspecialchars($user['role_name'] ?? '') . '</td>';
            echo '<td class="status" data-status="' . (int)$user['active'] . '">' . ((int)$user['active'] === 1 ? 'Active' : 'Inactive') . '</td>';
            echo '<td>
                <button class="crud-btn edit">Edit</button>
                <button class="crud-btn delete">Delete</button>
            </td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8" class="text-center">No active users found</td></tr>';
    }
    exit;
}

// AJAX: Update user
if (isset($_POST['ajax']) && $_POST['ajax'] === 'update_user') {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $role_id = trim($_POST['role_id']);
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $active = $status;
    $stmt = $pdo->prepare("UPDATE users SET username = :username, role_id = :role_id, active = :active WHERE id = :id");
    $ok = $stmt->execute([
        ':username' => $username,
        ':role_id' => $role_id,
        ':active' => $active,
        ':id' => $id
    ]);
    echo $ok ? 'success' : 'fail';
    exit;
}

// AJAX: Delete user
if (isset($_POST['ajax']) && $_POST['ajax'] === 'delete_user') {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $ok = $stmt->execute([':id' => $id]);
    echo $ok ? 'success' : 'fail';
    exit;
}

// AJAX: Bulk delete
if (isset($_POST['ajax']) && $_POST['ajax'] === 'bulk_delete') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (is_array($ids) && count($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($in)");
        $stmt->execute($ids);
    }
    echo 'success';
    exit;
}

// AJAX: Bulk status
if (isset($_POST['ajax']) && $_POST['ajax'] === 'bulk_status') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    $status = ($_POST['status'] === '1') ? 1 : 0;
    if (is_array($ids) && count($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE id IN ($in)");
        $params = array_merge([$status], $ids);
        $stmt->execute($params);
    }
    echo 'success';
    exit;
}

// AJAX: Bulk role
if (isset($_POST['ajax']) && $_POST['ajax'] === 'bulk_role') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    $role_id = trim($_POST['role_id']);
    if (is_array($ids) && count($ids) && $role_id !== '') {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id IN ($in)");
        $params = array_merge([$role_id], $ids);
        $stmt->execute($params);
    }
    echo 'success';
    exit;
}

// AJAX: Bulk export
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_export') {
    require_once __DIR__ . '/export_users.php';
    $ids = json_decode($_GET['ids'] ?? '[]', true);
    $filters = [
        'status' => isset($_GET['status']) ? $_GET['status'] : null,
        'role' => isset($_GET['role']) ? $_GET['role'] : null,
        'online' => isset($_GET['online']) ? $_GET['online'] : null,
        'search' => isset($_GET['search']) ? $_GET['search'] : null,
    ];
    export_users_csv($pdo, $ids, $filters);
    exit;
}
?>
 <script>
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
                    bindRowActions(); // Re-bind edit/delete after AJAX update
                });
        }

        document.querySelector('.refresh-btn').addEventListener('click', function(e) {
            e.preventDefault();
            fetchUsersTable();
        });

        document.getElementById('userSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetchUsersTable();
        });

        // Bulk actions
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

        function getSelectedUserIds() {
            return Array.from(document.querySelectorAll('.row-select:checked'))
                .map(cb => cb.closest('tr').getAttribute('data-id'));
        }

        // Select all checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        });

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
                    actionsTd.innerHTML = '<button class="crud-btn save">Save</button> <button class="crud-btn cancel">Cancel</button>';
                    actionsTd.querySelector('.save').onclick = function() {
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
                    };
                    actionsTd.querySelector('.cancel').onclick = function() {
                        fetchUsersTable();
                    };
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
        // Initial binding for edit/delete
        bindRowActions();
    });
    </script>

