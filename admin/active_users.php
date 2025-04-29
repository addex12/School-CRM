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
    <style>
        /* Sidebar adjustments */
        .admin-dashboard {
            display: flex;
            flex-wrap: wrap;
        }
        .admin-sidebar {
            flex: 0 0 250px;
            max-width: 250px;
            background-color: #f8f9fa;
            height: 100vh;
            overflow-y: auto;
        }
        .admin-main {
            flex: 1;
            padding: 1rem;
            margin-left: 250px;
        }
        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                width: 250px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* Button styling */
        .refresh-btn, .search-btn, .crud-btn {
            background-color: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .refresh-btn:hover, .search-btn:hover, .crud-btn:hover {
            background-color: #1565c0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .refresh-btn i, .search-btn i, .crud-btn i {
            font-size: 0.875rem;
        }

        /* Card styling */
        .active-users-container {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 0.75rem;
            margin-bottom: 1rem;
        }
        .active-users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        /* Table adjustments */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .users-table th, .users-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .users-table th {
            background-color: #f5f5f5;
        }
        .users-table .select-col {
            width: 40px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .active-users-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .users-table {
                font-size: 0.875rem;
            }
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
    <script>
        // Sidebar toggle for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const toggleButton = document.querySelector('.sidebar-toggle');
            toggleButton.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
