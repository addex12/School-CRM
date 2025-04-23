<?php
ob_start();
// Error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();


$pageTitle = "Active Users";

// Sorting/filtering logic
$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');
$roleSql = $roleFilter ? "AND r.role_name = :role" : "";
$searchSql = $search ? "AND (u.username LIKE :search OR u.email LIKE :search)" : "";

try {
    // Only show users with status 'active' and online=1
    $sql = "
        SELECT u.id, u.username, u.email, u.last_active, u.last_login, u.status, u.online,
               COALESCE(r.role_name, 'No Role') as role_name 
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.status = 'active'
          AND u.online = 1
        $roleSql
        $searchSql
        ORDER BY 
            COALESCE(u.last_active, u.last_login) DESC
    ";
    $stmt = $pdo->prepare($sql);
    if ($roleFilter) $stmt->bindValue(':role', $roleFilter);
    if ($search) $stmt->bindValue(':search', '%' . $search . '%');
    $stmt->execute();
    $activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format last activity time
    foreach ($activeUsers as &$user) {
        $user['last_active_display'] = $user['last_active'] 
            ? date('M j, Y g:i A', strtotime($user['last_active']))
            : ($user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : '-');
    }
    unset($user);

    // Fetch all roles for filter dropdown
    $roles = $pdo->query("SELECT DISTINCT role_name FROM roles WHERE role_name IS NOT NULL AND role_name != '' ORDER BY role_name")->fetchAll(PDO::FETCH_COLUMN);
    $users = $pdo->query("SELECT id, username, last_active FROM users WHERE online == 1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

    // Ensure $error is not set if query is successful
    unset($error);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "A database error occurred. Please try again later.";
    $activeUsers = [];
    $roles = [];
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
    $activeUsers = [];
    $roles = [];
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
    <style>
        .active-users-container {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .active-users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .active-count {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }
        .active-count i {
            color: #4CAF50;
        }
        .refresh-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .refresh-btn:hover {
            background: #2980b9;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .users-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .users-table tr:hover {
            background-color: #f5f5f5;
        }
        .status-active {
            color: #4CAF50;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
       
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <div class="admin-main">
            <div class="active-users-container">
                <div class="active-users-header">
                    <h2>Active Users</h2>
                    <div>
                        <button class="refresh-btn" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <span class="active-count">
                            <i class="fas fa-circle"></i>
                            <?= count($activeUsers ?? []) ?> active now
                        </span>
                    </div>
                </div>
                <form method="get" class="search-bar" id="activeUserSearchForm" style="margin-bottom:1.5rem;">
                    <input type="text" name="search" id="activeUserSearch" placeholder="Search by username or email..." value="<?= htmlspecialchars($search) ?>">
                    <select name="role" id="roleFilter">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>" <?= $role === $roleFilter ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="erpnext-btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="active_users.php" class="erpnext-btn btn-secondary">Clear</a>
                </form>
                <div class="online-users-list">
                    <i class="fas fa-circle" style="color:#27ae60;font-size:0.9em;"></i>
                    Online:&nbsp;
                    <?php
                    $onlineList = [];
                    if (!empty($activeUsers) && is_array($activeUsers)) {
                        foreach ($activeUsers as $user) {
                            $onlineList[] = '<span class="online-user-pill">' . htmlspecialchars($user['username']) . '</span>';
                        }
                    }
                    echo $onlineList ? implode('', $onlineList) : '<span style="color:#888;">No users online</span>';
                    ?>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php else: ?>
                    <table class="users-table" id="activeUsersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Last Active</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="activeUsersTbody">
                            <?php if (!empty($activeUsers)): ?>
                                <?php foreach ($activeUsers as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                                    <td><?= htmlspecialchars($user['last_active_display']) ?></td>
                                    <td class="status-active">Active</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No active users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
    <script>
        // Real-time search/filter (client-side for current page)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('activeUserSearch');
            const roleFilter = document.getElementById('roleFilter');
            const tbody = document.getElementById('activeUsersTbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            function filterRows() {
                const val = searchInput.value.toLowerCase();
                const role = roleFilter.value;
                rows.forEach(function(row) {
                    const cells = row.querySelectorAll('td');
                    if (!cells.length) return;
                    const username = cells[1].textContent.toLowerCase();
                    const email = cells[2].textContent.toLowerCase();
                    const userRole = cells[3].textContent;
                    const match = (!val || username.includes(val) || email.includes(val));
                    const roleMatch = (!role || userRole === role);
                    row.style.display = (match && roleMatch) ? '' : 'none';
                });
            }
            searchInput.addEventListener('input', filterRows);
            roleFilter.addEventListener('change', filterRows);
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>