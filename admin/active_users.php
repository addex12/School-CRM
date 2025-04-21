<?php
ob_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();
$pageTitle = "Active Users";

// Fetch users active in the last 5 minutes
$activeUsers = [];
$threshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
try {
    $stmt = $pdo->prepare("SELECT u.username, u.email, r.role_name, u.last_active FROM users u JOIN roles r ON u.role_id = r.id WHERE u.last_active >= ? ORDER BY u.last_active DESC");
    $stmt->execute([$threshold]);
    $activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching active users: " . $e->getMessage());
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
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section">
                    <h2>Currently Active Users (last 5 minutes)</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Active</th>
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
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5">No active users found.</td></tr>
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
