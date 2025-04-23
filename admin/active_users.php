<?php
ob_start();
session_start();

// Error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Include required files
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/config.php';

// Verify admin access
requireAdmin();

$pageTitle = "Active Users";

// Get search term if exists
$searchTerm = isset($_GET['all_search']) ? trim($_GET['all_search']) : '';

try {
    // Get active users (last 15 minutes)
    $activeThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    // Base query
    $sql = "
        SELECT u.id, u.username, u.email, u.last_activity, 
               COALESCE(r.role_name, 'No Role') as role_name 
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.last_activity >= :threshold
    ";
    
    // Initialize parameters array
    $params = [':threshold' => $activeThreshold];
    
    // Add search condition if search term exists
    if (!empty($searchTerm)) {
        $sql .= " AND (u.username LIKE :search OR u.email LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    // Complete the query with ordering
    $sql .= " ORDER BY u.last_activity DESC";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    
    $stmt->execute();
    
    $activeUsers = $stmt->fetchAll();

    // Format last activity time
    foreach ($activeUsers as &$user) {
        $user['last_active'] = date('M j, Y g:i A', strtotime($user['last_activity']));
    }
    unset($user);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "A database error occurred. Please try again later.";
    // For debugging, you can temporarily show the actual error:
    // $error = "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
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
        /* ... (keep all your existing styles) ... */
        .search-container {
            margin-bottom: 20px;
        }
        .search-input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
        }
        .search-btn {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
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

                <!-- Search Form -->
                <div class="search-container">
                    <form method="GET" action="">
                        <input type="text" name="all_search" class="search-input" 
                               placeholder="Search by username or email..." 
                               value="<?= htmlspecialchars($searchTerm) ?>">
                        <button type="submit" class="search-btn">Search</button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="?" class="search-btn">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php else: ?>
                    <table class="users-table">
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
                        <tbody>
                            <?php if (!empty($activeUsers)): ?>
                                <?php foreach ($activeUsers as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                                    <td><?= htmlspecialchars($user['last_active']) ?></td>
                                    <td class="status-active">Active</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        No active users found<?= !empty($searchTerm) ? ' matching your search' : '' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>