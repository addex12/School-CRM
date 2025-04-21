<?php
// Start output buffering
ob_start();

// Set error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define root path

try {
    // Include required files
    require '../includes/db.php'; // Database connection first
    require  '../includes/auth.php';
    
    // Check if admin
    if (!function_exists('requireAdmin')) {
        throw new Exception("Authentication system not available");
    }
    requireAdmin();

    // Verify database connection
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection failed");
    }

    // Set page title
    $pageTitle = "Active Users";

    // Get active users (last 15 minutes)
    $activeThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.last_activity, 
               COALESCE(r.role_name, 'No Role') as role_name 
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.last_activity >= :threshold
        ORDER BY u.last_activity DESC
    ");
    
    $stmt->bindParam(':threshold', $activeThreshold);
    $stmt->execute();
    
    $activeUsers = $stmt->fetchAll();

    // Format last activity time
    foreach ($activeUsers as &$user) {
        $user['last_active'] = date('M j, Y g:i A', strtotime($user['last_activity']));
    }
    unset($user);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    die("A database error occurred. Please try again later.");
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    http_response_code(500);
    die("An application error occurred: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include ROOT_PATH . '/admin/includes/admin_sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-header">
            <h1>Active Users</h1>
            <div class="active-count">
                <i class="fas fa-users"></i>
                <span><?= count($activeUsers) ?> active</span>
            </div>
        </header>

        <div class="content">
            <div class="card">
                <div class="card-header">
                    <button id="refreshBtn" class="btn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <span>Last updated: <?= date('g:i A') ?></span>
                </div>
                
                <div class="card-body">
                    <table class="table">
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
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                                    <td><?= htmlspecialchars($user['last_active']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No active users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('refreshBtn').addEventListener('click', function() {
        window.location.reload();
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>