<?php
ob_start();
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = "Active Users";

// Database connection
require_once '../includes/db.php';

// Define what we consider "active" (e.g., users active in the last 15 minutes)
$activeThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));

// Query to get active users with their role information
$query = "SELECT u.id, u.username, u.email, u.last_activity, r.role_name 
          FROM users u
          LEFT JOIN roles r ON u.role_id = r.id
          WHERE u.last_activity >= :threshold
          ORDER BY u.last_activity DESC";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':threshold', $activeThreshold);
$stmt->execute();

$activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format the last activity time for display
foreach ($activeUsers as &$user) {
    $user['last_active'] = date('M j, Y g:i A', strtotime($user['last_activity']));
}
unset($user); // Break the reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/js/admin_users.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="active-users-count">
                    <i class="fas fa-users"></i>
                    <span><?= count($activeUsers) ?> active now</span>
                </div>
            </header>
            <div class="content">
                <div class="dashboard-section">
                    <div class="table-actions">
                        <div class="refresh-controls">
                            <button id="refreshUsers" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <span class="last-updated">Last updated: <?= date('g:i A') ?></span>
                        </div>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Active</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($activeUsers) > 0): ?>
                                    <?php foreach ($activeUsers as $idx => $user): ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['role_name']) ?></td>
                                            <td><?= htmlspecialchars($user['last_active']) ?></td>
                                            <td>
                                                <span class="status-badge active">
                                                    <i class="fas fa-circle"></i> Active
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="no-data-message">
                                                <i class="fas fa-user-clock"></i>
                                                <p>No active users at this time</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>