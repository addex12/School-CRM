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

$where = ["active = 1"];
$params = [];

if ($search !== '') {
    $where[] = "username LIKE :search";
    $params[':search'] = "%$search%";
}
if ($filter_online) {
    $where[] = "online = 1";
}

$where_sql = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare("SELECT id, username, last_active, online FROM users WHERE $where_sql ORDER BY username");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For stats
    $total_active = $pdo->query("SELECT COUNT(*) FROM users WHERE active = 1")->fetchColumn();
    $total_online = $pdo->query("SELECT COUNT(*) FROM users WHERE online = 1")->fetchColumn();

    unset($error);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "A database error occurred. Please try again later.";
    $users = [];
    $total_active = 0;
    $total_online = 0;
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
        @media (max-width: 700px) {
            .active-users-container { padding: 10px; }
            .users-table th, .users-table td { padding: 7px 4px; }
            .erpnext-search-form { flex-direction: column; align-items: flex-start; gap: 7px; }
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

                <form class="erpnext-search-form" method="get" action="">
                    <input type="text" name="search" placeholder="Search username..." value="<?= htmlspecialchars($search) ?>">
                    <label>
                        <input type="checkbox" name="online" value="1" <?= $filter_online ? 'checked' : '' ?>>
                        Online only
                    </label>
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                </form>

                <table class="users-table" id="allUsersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Last Active</th>
                            <th>Online</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($users) && is_array($users) && count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['last_active'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($user['online'])): ?>
                                        <span class="online-dot"></span> <span style="color:#27ae60;font-weight:500;">Online</span>
                                    <?php else: ?>
                                        <span style="color:#aaa;">Offline</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No active users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>