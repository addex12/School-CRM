<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Active Users";

// Fetch active users (last activity within 15 minutes)
$stmt = $pdo->query("
    SELECT u.id, u.username, u.email, r.role_name, u.last_activity 
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.last_activity >= NOW() - INTERVAL 15 MINUTE
    ORDER BY u.last_activity DESC
");
$activeUsers = $stmt->fetchAll();

include 'includes/admin_sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .active-users-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .active-users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .active-users-table th, .active-users-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .active-users-table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .no-active-users {
            text-align: center;
            color: #666;
            font-size: 1.1em;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="active-users-container">
                <?php if (count($activeUsers) > 0): ?>
                    <table class="active-users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeUsers as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role_name'] ?? 'N/A') ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($user['last_activity'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-active-users">No active users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
