<?php
/**
 * Notifications Management
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Notifications";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Mark notifications as read if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['success'] = "All notifications marked as read";
    } catch (Exception $e) {
        error_log("Mark all read error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to mark notifications as read";
    }
}

// Build query
$query = "SELECT * FROM notifications WHERE user_id = :user_id";
$params = [':user_id' => $_SESSION['user_id']];

// Apply filters
if ($status !== 'all') {
    $query .= " AND is_read = :is_read";
    $params[':is_read'] = ($status === 'read') ? 1 : 0;
}

if ($type !== 'all') {
    $query .= " AND notification_type = :notification_type";
    $params[':notification_type'] = $type;
}

if (!empty($search)) {
    $query .= " AND (title LIKE :search OR message LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add sorting
$query .= " ORDER BY created_at DESC";

// Add pagination
$query .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = (int)$itemsPerPage;
$params[':offset'] = (int)$offset;

$stmt = $pdo->prepare($query);

// Bind parameters properly
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}

$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Manage your notifications</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge"><?= countUnreadNotifications($pdo, $_SESSION['user_id']) ?></span>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications dropdown content -->
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <!-- Filters Section -->
                <div class="filters-section">
                    <form method="get" class="filter-form">
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Notifications</option>
                                <option value="unread" <?= $status === 'unread' ? 'selected' : '' ?>>Unread Only</option>
                                <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Read Only</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="type">Type:</label>
                            <select name="type" id="type">
                                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>All Types</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? 'selected' : '' ?>>
                                        <?= ucwords(str_replace('_', ' ', htmlspecialchars($t))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group search-group">
                            <input type="text" name="search" placeholder="Search notifications..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <button type="submit" class="filter-button">Apply Filters</button>
                        <a href="?status=all&type=all&search=&mark_read=all" class="btn btn-secondary">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </a>
                    </form>
                </div>
                
                <!-- Notifications List -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-bell"></i> Your Notifications</h2>
                        <div class="notification-actions">
                            <a href="#" id="select-all" class="btn btn-sm btn-outline">Select All</a>
                            <a href="#" id="mark-selected-read" class="btn btn-sm btn-outline">
                                <i class="fas fa-check"></i> Mark Selected as Read
                            </a>
                            <a href="#" id="delete-selected" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i> Delete Selected
                            </a>
                        </div>
                    </div>
                    
                    <div class="notifications-container">
                        <?php if (!empty($notifications)): ?>
                            <form id="notifications-form" method="post" action="notification_actions.php">
                                <div class="notifications-list">
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="notification-item <?= $notification['is_read'] ? 'read' : 'unread' ?>">
                                            <div class="notification-checkbox">
                                                <input type="checkbox" name="notification_ids[]" value="<?= $notification['id'] ?>">
                                            </div>
                                            <div class="notification-icon">
                                                <?php switch($notification['notification_type']) {
                                                    case 'system': echo '<i class="fas fa-cog"></i>'; break;
                                                    case 'alert': echo '<i class="fas fa-exclamation-triangle"></i>'; break;
                                                    case 'message': echo '<i class="fas fa-envelope"></i>'; break;
                                                    case 'ticket': echo '<i class="fas fa-ticket-alt"></i>'; break;
                                                    default: echo '<i class="fas fa-bell"></i>';
                                                } ?>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-header">
                                                    <h4><?= htmlspecialchars($notification['title']) ?></h4>
                                                    <small><?= formatDate($notification['created_at']) ?></small>
                                                </div>
                                                <p><?= htmlspecialchars($notification['message']) ?></p>
                                                <?php if (!empty($notification['action_url'])): ?>
                                                    <a href="<?= htmlspecialchars($notification['action_url']) ?>" class="notification-action">
                                                        View Details <i class="fas fa-arrow-right"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="notification-actions">
                                                <a href="notification_actions.php?action=mark_read&id=<?= $notification['id'] ?>" class="btn btn-sm btn-mark-read">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="notification_actions.php?action=delete&id=<?= $notification['id'] ?>" class="btn btn-sm btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                            
                            <!-- Pagination -->
                            <div class="pagination">
                                <?php if ($totalPages > 1): ?>
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="page-link">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                           class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="page-link">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-link">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications found</p>
                                <?php if ($status !== 'all' || $type !== 'all' || !empty($search)): ?>
                                    <a href="notifications.php" class="btn btn-primary">
                                        <i class="fas fa-undo"></i> Reset Filters
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>