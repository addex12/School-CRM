<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Audit Log";

// Fetch audit logs
$stmt = $pdo->query("
    SELECT a.*, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
");
$auditLogs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                    <?php if (count($auditLogs) > 0): ?>
                        <table class="table">
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
