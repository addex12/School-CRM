<?php
ob_start();

// Error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Required files
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/db.php';
    global $pdo;
    
    // Verify admin access
    if (!function_exists('requireAdmin')) {
        throw new Exception("Authentication functions not available");
    }
    requireAdmin();
    
    $pageTitle = "Active Users";

    // Define active threshold (15 minutes)
    $activeThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));

    // Get active users
    $query = "SELECT u.id, u.username, u.email, u.last_activity, 
                     IFNULL(r.role_name, 'No Role') as role_name 
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.id
              WHERE u.last_activity >= :threshold
              ORDER BY u.last_activity DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':threshold', $activeThreshold, PDO::PARAM_STR);
    $stmt->execute();
    
    $activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format last activity time
    foreach ($activeUsers as &$user) {
        $user['last_active'] = date('M j, Y g:i A', strtotime($user['last_activity']));
    }
    unset($user);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Panel') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle ?? 'Active Users') ?></h1>
                <div class="active-users-count">
                    <i class="fas fa-users"></i>
                    <span><?= count($activeUsers ?? []) ?> active now</span>
                </div>
            </header>
            
            <div class="content">
                <div class="dashboard-section">
                    <div class="table-actions">
                        <button id="refreshUsers" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <span class="last-updated">Last updated: <?= date('g:i A') ?></span>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activeUsers)): ?>
                                    <?php foreach ($activeUsers as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['id'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($user['username'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($user['role_name'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($user['last_active'] ?? 'Never') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            No active users found.
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
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script>
    // Simple refresh functionality
    document.getElementById('refreshUsers').addEventListener('click', function() {
        window.location.reload();
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>