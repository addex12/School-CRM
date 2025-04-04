<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Audit Log";

// Load audit log configuration
$auditConfig = json_decode(file_get_contents(__DIR__ . '/audit_log.json'), true);

// Fetch audit logs with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
    LIMIT :offset, :perPage
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$auditLogs = $stmt->fetchAll();

// Get total count for pagination
$totalStmt = $pdo->query("SELECT COUNT(*) FROM audit_logs");
$totalLogs = $totalStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/audit_log.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <section class="table-section">
                    <h2>Audit Logs</h2>
                    <div class="search-container">
                        <input type="text" id="audit-log-search" placeholder="Search logs..." class="form-control">
                        <button id="export-audit-log" class="btn btn-primary">Export as CSV</button>
                    </div>
                    <?php if (count($auditLogs) > 0): ?>
                        <table class="audit-log-table table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['id']) ?></td>
                                        <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['details'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="btn btn-secondary">Previous</a>
                            <?php endif; ?>
                            
                            <span>Page <?= $page ?> of <?= $totalPages ?></span>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="btn btn-secondary">Next</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>No audit logs found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>