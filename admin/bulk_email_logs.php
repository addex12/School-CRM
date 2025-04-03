<?php
/**
 * Bulk Email Logs
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Bulk Email Logs";

// Pagination
$itemsPerPage = 20;
$currentPage = $_GET['page'] ?? 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count
try {
    $countQuery = "SELECT COUNT(*) FROM bulk_email_logs";
    $totalItems = $pdo->query($countQuery)->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // Get paginated logs
    $query = "SELECT l.*, u.username as sender,
              COUNT(CASE WHEN r.status = 'sent' THEN 1 END) as success_count,
              COUNT(CASE WHEN r.status = 'failed' THEN 1 END) as error_count
              FROM bulk_email_logs l
              JOIN users u ON l.user_id = u.id
              LEFT JOIN bulk_email_recipients r ON l.id = r.bulk_email_id
              GROUP BY l.id
              ORDER BY l.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$itemsPerPage, $offset]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Bulk email logs error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load bulk email logs";
    $logs = [];
    $totalPages = 1;
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
    <link rel="stylesheet" href="../assets/css/bulk_email.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Historical bulk email records</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <!-- Notifications dropdown -->
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="bulk-email-logs">
                    <div class="logs-header">
                        <h2><i class="fas fa-history"></i> Bulk Email History</h2>
                        <a href="bulk_email.php" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> New Bulk Email
                        </a>
                    </div>
                    
                    <?php if (!empty($logs)): ?>
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Sender</th>
                                    <th>Recipients</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $log['id'] ?></td>
                                        <td><?= htmlspecialchars($log['subject']) ?></td>
                                        <td><?= htmlspecialchars($log['sender']) ?></td>
                                        <td><?= $log['total_recipients'] ?></td>
                                        <td class="success"><?= $log['success_count'] ?></td>
                                        <td class="error"><?= $log['error_count'] ?></td>
                                        <td><?= formatDate($log['created_at']) ?></td>
                                        <td>
                                            <a href="bulk_email_details.php?id=<?= $log['id'] ?>" class="btn btn-sm btn-view">
                                                <i class="fas fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <?php if ($totalPages > 1): ?>
                                <?php if ($currentPage > 1): ?>
                                    <a href="?page=1" class="page-link">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?page=<?= $currentPage - 1 ?>" class="page-link">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=<?= $i ?>" 
                                       class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?page=<?= $currentPage + 1 ?>" class="page-link">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?page=<?= $totalPages ?>" class="page-link">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-logs">
                            <i class="fas fa-inbox"></i>
                            <p>No bulk email logs found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>