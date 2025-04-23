<?php
ob_start();
// Error reporting (remove in production)
error_log($allSql);
error_log(print_r($allParams, true));
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Include required files
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Active Users";

// Place this block here, after DB connection is established
$allSearch = isset($_GET['all_search']) ? trim($_GET['all_search']) : '';
$allConditions = ["status = 'active'"];
$allParams = [];
if ($allSearch) {
    $allConditions[] = "(username LIKE :search_username OR name LIKE :search_name OR email LIKE :search_email)";
    $allParams[':search_username'] = '%' . $allSearch . '%';
    $allParams[':search_name'] = '%' . $allSearch . '%';
    $allParams[':search_email'] = '%' . $allSearch . '%';
}
$allWhereSql = 'WHERE ' . implode(' AND ', $allConditions);

// Fetch all roles for filter dropdown (do this first, always)
try {
    $roles = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role != '' ORDER BY role")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $roles = [];
}

try {
    // Only select id, username, last_active, correct WHERE syntax
    $allSql = "SELECT id, username, last_active FROM users $allWhereSql ORDER BY username";
    $stmtAll = $pdo->prepare($allSql);
    // Fix: Only bind if $allParams is not empty
    if (!empty($allParams)) {
        foreach ($allParams as $key => $val) {
            $stmtAll->bindValue($key, $val);
        }
    }
    $stmtAll->execute();
    $allUsers = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

    unset($error);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "A database error occurred. Please try again later.";
    $allUsers = [];
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
        .online-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #27ae60;
            border-radius: 50%;
            margin-right: 7px;
        }
        .online-user-pill {
            display: inline-block;
            background: #27ae60;
            color: #fff;
            border-radius: 1em;
            padding: 0.2em 0.9em;
            font-size: 0.97em;
            margin-right: 0.4em;
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
                            <?= count($allUsers ?? []) ?> active
                        </span>
                    </div>
                </div>
                <?php if (!empty($error)): ?>
                    <div style="color: red; margin-bottom: 1em;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="get" class="search-bar" id="allUserSearchForm" style="margin-bottom:1.5rem;">
                    <input type="text" name="all_search" id="allUserSearch" placeholder="Search by username, name, or email..." value="<?= htmlspecialchars($allSearch ?? '') ?>">
                    <!-- Remove role filter dropdown -->
                    <button type="submit" class="erpnext-btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="active_users.php" class="erpnext-btn btn-secondary">Clear</a>
                </form>
                <table class="users-table" id="allUsersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Last Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($allUsers) && is_array($allUsers) && count($allUsers) > 0): ?>
                            <?php foreach ($allUsers as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['last_active'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No active users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
    <script>
        // Real-time search/filter (client-side for current page)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('activeUserSearch');
            const roleFilter = document.getElementById('roleFilter');
            function filterTables() {
                const val = searchInput.value.toLowerCase();
                const role = roleFilter.value;
                ['onlineUsersTable', 'allUsersTable'].forEach(function(tableId) {
                    const tbody = document.getElementById(tableId).querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    rows.forEach(function(row) {
                        const cells = row.querySelectorAll('td');
                        if (!cells.length) return;
                        const username = cells[1].textContent.toLowerCase();
                        const match = (!val || username.includes(val));
                        row.style.display = match ? '' : 'none';
                    });
                });
            }
            searchInput.addEventListener('input', filterTables);
            roleFilter.addEventListener('change', filterTables);
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>