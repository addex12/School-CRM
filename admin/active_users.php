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

// Remove all search/filter logic, just fetch active users
try {
    $users = $pdo->query("SELECT id, username, last_active, online FROM users WHERE status = 'active' ORDER BY online DESC, username")->fetchAll(PDO::FETCH_ASSOC);
    unset($error);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "A database error occurred. Please try again later.";
    $users = [];
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
                            <?= count($users ?? []) ?> active
                        </span>
                    </div>
                </div>
                <?php if (!empty($error)): ?>
                    <div style="color: red; margin-bottom: 1em;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Remove search form entirely -->

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
                                        <span class="online-dot"></span> Online
                                    <?php else: ?>
                                        Offline
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