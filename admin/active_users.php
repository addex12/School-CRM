<?php
ob_start();
// Error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Include required files
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Active Users";

// Handle search/filter
$search = trim($_GET['search'] ?? '');
$filter_online = isset($_GET['online']) && $_GET['online'] === '1';
$role_id = trim($_GET['role'] ?? '');

$where = ["u.active = 1"];
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

$where_sql = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.last_active, u.online, u.role_id, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE $where_sql
        ORDER BY u.username");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For stats: count active and online users from the FULL users table, not filtered
    $total_active = $pdo->query("SELECT COUNT(*) FROM users WHERE active = 1")->fetchColumn();
    $total_online = $pdo->query("SELECT COUNT(*) FROM users WHERE active = 1 AND online = 1")->fetchColumn();

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

    unset($error);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    //$error = "A database error occurred. Please try again later.";
    $users = [];
    $total_active = 0;
    $total_online = 0;
}

// Handle AJAX request for real-time search/filter
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    require_once '../includes/config.php';
    $search = trim($_GET['search'] ?? '');
    $filter_online = isset($_GET['online']) && $_GET['online'] === '1';
    $role_id = trim($_GET['role'] ?? '');

    $where = ["u.active = 1"];
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

    $where_sql = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT u.id, u.username, u.last_active, u.online, u.role_id, r.role_name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE $where_sql
        ORDER BY u.username");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Split users into online and offline
    $online_users = [];
    $offline_users = [];
    foreach ($users as $user) {
        if (!empty($user['online'])) {
            $online_users[] = $user;
        } else {
            $offline_users[] = $user;
        }
    }

    ob_clean();
    if (count($online_users) + count($offline_users) === 0) {
        echo '<tr><td colspan="6" class="text-center">No active users found</td></tr>';
    } else {
        foreach ($online_users as $user) {
            echo '<tr>
                <td>' . htmlspecialchars($user['id']) . '</td>
                <td>' . htmlspecialchars($user['username']) . '</td>
                <td>' . htmlspecialchars($user['last_active'] ?? '') . '</td>
                <td><span class="online-dot"></span> <span style="color:#27ae60;font-weight:500;">Online</span></td>
                <td>' . htmlspecialchars($user['role_name']) . '</td>
                <td>
                    <button class="crud-btn edit">Edit</button>
                    <button class="crud-btn delete">Delete</button>
                </td>
            </tr>';
        }
        foreach ($offline_users as $user) {
            echo '<tr>
                <td>' . htmlspecialchars($user['id']) . '</td>
                <td>' . htmlspecialchars($user['username']) . '</td>
                <td>' . htmlspecialchars($user['last_active'] ?? '') . '</td>
                <td><span style="color:#aaa;">Offline</span></td>
                <td>' . htmlspecialchars($user['role_name']) . '</td>
                <td>
                    <button class="crud-btn edit">Edit</button>
                    <button class="crud-btn delete">Delete</button>
                </td>
            </tr>';
        }
    }
    exit;
}

// Handle AJAX update (edit user)
if (isset($_POST['ajax']) && $_POST['ajax'] === 'update_user') {
    require_once '../includes/config.php';
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $role_id = trim($_POST['role_id']);
    $active = isset($_POST['active']) ? 1 : 0;
    $online = isset($_POST['online']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE users SET username = :username, role_id = :role_id, active = :active, online = :online WHERE id = :id");
    $ok = $stmt->execute([
        ':username' => $username,
        ':role_id' => $role_id,
        ':active' => $active,
        ':online' => $online,
        ':id' => $id
    ]);
    echo $ok ? 'success' : 'fail';
    exit;
}

// Handle AJAX delete
if (isset($_POST['ajax']) && $_POST['ajax'] === 'delete_user') {
    require_once '../includes/config.php';
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $ok = $stmt->execute([':id' => $id]);
    echo $ok ? 'success' : 'fail';
    exit;
}

// Fetch roles for filter dropdown (id => name)
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_KEY_PAIR);

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
    <style>
        body {
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background: #f4f7fa;
        }
        .active-users-container {
            padding: 24px 32px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            margin-top: 32px;
        }
        .active-users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e3e8ee;
        }
        .active-count {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #555;
            font-size: 1.08em;
        }
        .active-count i {
            color: #4CAF50;
        }
        .refresh-btn, .search-btn {
            background: #1976d2;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 1em;
            font-weight: 500;
            transition: background 0.2s;
        }
        .refresh-btn:hover, .search-btn:hover {
            background: #125ea2;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.04em;
        }
        .users-table th {
            background: #f1f5fa;
            padding: 13px 10px;
            text-align: left;
            border-bottom: 2px solid #e3e8ee;
            color: #1976d2;
            font-weight: 600;
        }
        .users-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .users-table tr:hover {
            background-color: #f6fafd;
        }
        .status-active {
            color: #4CAF50;
            font-weight: 500;
        }
        .online-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #27ae60;
            border-radius: 50%;
            margin-right: 7px;
        }
        .erpnext-search-form {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .erpnext-search-form input[type="text"] {
            padding: 7px 12px;
            border: 1px solid #cfd8dc;
            border-radius: 5px;
            font-size: 1em;
            outline: none;
            transition: border 0.2s;
        }
        .erpnext-search-form input[type="text"]:focus {
            border: 1.5px solid #1976d2;
        }
        .erpnext-search-form label {
            font-size: 1em;
            color: #1976d2;
            font-weight: 500;
        }
        .erpnext-search-form input[type="checkbox"] {
            accent-color: #1976d2;
            width: 16px;
            height: 16px;
        }
        .erpnext-search-form select {
            padding: 7px 12px;
            border: 1px solid #cfd8dc;
            border-radius: 5px;
            font-size: 1em;
            outline: none;
            transition: border 0.2s;
            background: #fff;
            color: #1976d2;
        }
        .erpnext-search-form select:focus {
            border: 1.5px solid #1976d2;
        }
        @media (max-width: 700px) {
            .active-users-container { padding: 10px; }
            .users-table th, .users-table td { padding: 7px 4px; }
            .erpnext-search-form { flex-direction: column; align-items: flex-start; gap: 7px; }
        }
        .crud-btn {
            background: #fff;
            border: 1px solid #1976d2;
            color: #1976d2;
            border-radius: 4px;
            padding: 4px 10px;
            margin-right: 4px;
            cursor: pointer;
            font-size: 0.98em;
            transition: background 0.2s, color 0.2s;
        }
        .crud-btn:hover {
            background: #1976d2;
            color: #fff;
        }
        .crud-btn.delete {
            border-color: #e53935;
            color: #e53935;
        }
        .crud-btn.delete:hover {
            background: #e53935;
            color: #fff;
        }
        .crud-btn.save {
            border-color: #388e3c;
            color: #388e3c;
        }
        .crud-btn.save:hover {
            background: #388e3c;
            color: #fff;
        }
        .crud-btn.cancel {
            border-color: #aaa;
            color: #888;
        }
        .crud-btn.cancel:hover {
            background: #eee;
            color: #333;
        }
        .crud-editable {
            background: #f9f9f9;
            border: 1px solid #cfd8dc;
            border-radius: 3px;
            padding: 3px 7px;
            font-size: 1em;
        }
    </style>
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
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                </form>

                <table class="users-table" id="allUsersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Last Active</th>
                            <th>Online</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php
                        $has_users = (isset($online_users) && count($online_users) > 0) || (isset($offline_users) && count($offline_users) > 0);
                        if ($has_users):
                        ?>
                            <!-- Online users first -->
                            <?php foreach ($online_users as $user): ?>
                            <tr data-id="<?= htmlspecialchars($user['id']) ?>">
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td class="username"><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['last_active'] ?? '') ?></td>
                                <td class="online">
                                    <span class="online-dot"></span> <span style="color:#27ae60;font-weight:500;">Online</span>
                                </td>
                                <td class="role" data-role-id="<?= htmlspecialchars($user['role_id']) ?>"><?= htmlspecialchars($user['role_name']) ?></td>
                                <td>
                                    <button class="crud-btn edit">Edit</button>
                                    <button class="crud-btn delete">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Offline users next -->
                            <?php foreach ($offline_users as $user): ?>
                            <tr data-id="<?= htmlspecialchars($user['id']) ?>">
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td class="username"><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['last_active'] ?? '') ?></td>
                                <td class="online">
                                    <span style="color:#aaa;">Offline</span>
                                </td>
                                <td class="role" data-role-id="<?= htmlspecialchars($user['role_id']) ?>"><?= htmlspecialchars($user['role_name']) ?></td>
                                <td>
                                    <button class="crud-btn edit">Edit</button>
                                    <button class="crud-btn delete">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No active users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
    <script>
    // Real-time AJAX search/filter
    const searchInput = document.getElementById('searchInput');
    const onlineInput = document.getElementById('onlineInput');
    const roleInput = document.getElementById('roleInput');
    const usersTableBody = document.getElementById('usersTableBody');
    const form = document.getElementById('userSearchForm');
    let searchTimeout = null;

    function fetchUsers() {
        const params = new URLSearchParams();
        params.append('ajax', '1');
        params.append('search', searchInput.value);
        if (onlineInput.checked) params.append('online', '1');
        if (roleInput.value) params.append('role', roleInput.value);

        fetch('active_users.php?' + params.toString())
            .then(res => res.text())
            .then(html => {
                usersTableBody.innerHTML = html;
            });
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchUsers, 250);
    });
    onlineInput.addEventListener('change', fetchUsers);
    roleInput.addEventListener('change', fetchUsers);

    // Also fetch on form submit (search button)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchUsers();
    });

    // Inline CRUD logic
    function makeEditableRow(tr) {
        const id = tr.getAttribute('data-id');
        const usernameTd = tr.querySelector('.username');
        const roleTd = tr.querySelector('.role');
        const onlineTd = tr.querySelector('.online');
        const actionsTd = tr.querySelector('td:last-child');

        // Save original values
        const orig = {
            username: usernameTd.textContent.trim(),
            role_id: roleTd.getAttribute('data-role-id'),
            online: /Online/i.test(onlineTd.textContent),
        };

        // Replace with inputs
        usernameTd.innerHTML = `<input class="crud-editable" name="username" value="${orig.username}">`;
        // Role select
        let roleOptions = `<option value="">Select</option>`;
        <?php foreach ($roles as $id => $name): ?>
            roleOptions += `<option value="<?= htmlspecialchars($id) ?>" ${orig.role_id == "<?= htmlspecialchars($id) ?>" ? 'selected' : ''}><?= htmlspecialchars($name) ?></option>`;
        <?php endforeach; ?>
        roleTd.innerHTML = `<select class="crud-editable" name="role">${roleOptions}</select>`;
        // Online checkbox
        onlineTd.innerHTML = `<label><input type="checkbox" name="online" ${orig.online ? 'checked' : ''}> Online</label>`;

        // Actions: Save/Cancel
        actionsTd.innerHTML =
            `<button class="crud-btn save">Save</button>
             <button class="crud-btn cancel">Cancel</button>`;

        // Save handler
        actionsTd.querySelector('.save').onclick = function() {
            const username = usernameTd.querySelector('input').value.trim();
            const role_id = roleTd.querySelector('select').value;
            const online = onlineTd.querySelector('input[type="checkbox"]').checked ? 1 : 0;
            fetch('../api/user_crud.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update',
                    id: id,
                    username: username,
                    role_id: role_id,
                    online: online,
                    active: 1
                })
            }).then(res => res.json()).then(resp => {
                if (resp.status === 'success') {
                    fetchUsers();
                } else {
                    alert('Update failed');
                }
            });
        };
        // Cancel handler
        actionsTd.querySelector('.cancel').onclick = function() {
            fetchUsers();
        };
    }

    // Delete handler
    function deleteRow(tr) {
        const id = tr.getAttribute('data-id');
        if (!confirm('Are you sure you want to delete this user?')) return;
        fetch('../api/user_crud.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'delete',
                id: id
            })
        }).then(res => res.json()).then(resp => {
            if (resp.status === 'success') {
                fetchUsers();
            } else {
                alert('Delete failed');
            }
        });
    }

    // Delegate edit/delete buttons
    function delegateCrud() {
        document.querySelectorAll('#usersTableBody tr').forEach(tr => {
            const editBtn = tr.querySelector('.edit');
            const deleteBtn = tr.querySelector('.delete');
            if (editBtn) editBtn.onclick = () => makeEditableRow(tr);
            if (deleteBtn) deleteBtn.onclick = () => deleteRow(tr);
        });
    }

    // After AJAX update, re-delegate events
    function fetchUsersAndDelegate() {
        fetchUsers();
        setTimeout(delegateCrud, 350);
    }

    // Patch fetchUsers to call delegateCrud after update
    const origFetchUsers = fetchUsers;
    fetchUsers = function() {
        origFetchUsers();
        setTimeout(delegateCrud, 350);
    };

    // Initial delegate
    delegateCrud();

    // Ensure delegateCrud is called after every AJAX update
    // Add MutationObserver to handle dynamic table updates
    const observer = new MutationObserver(delegateCrud);
    observer.observe(usersTableBody, { childList: true });

    // On page load, fetch all users by default (no filter)
    document.addEventListener('DOMContentLoaded', function() {
        fetchUsers();
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>