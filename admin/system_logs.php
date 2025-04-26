<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/db.php';

$pageTitle = "System Logs";

// Fetch logs from the database
$logs = [];
try {
    $stmt = $pdo->query("SELECT id, log_level, message, created_at FROM system_logs ORDER BY created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to fetch system logs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .logs-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .logs-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .logs-header h1 {
            font-size: 1.5rem;
            color: #34495e;
            margin: 0;
        }
        .logs-header .btn {
            font-size: 0.85rem;
            padding: 0.3rem 0.6rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th, .table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background: #f4f6f9;
            color: #34495e;
        }
        .table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .log-level {
            font-weight: bold;
            text-transform: uppercase;
        }
        .log-level.error {
            color: #e74c3c;
        }
        .log-level.warning {
            color: #f39c12;
        }
        .log-level.info {
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-file-alt"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="logs-container">
                    <div class="logs-header">
                        <h1>System Logs</h1>
                        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    </div>
                    <div>
                        <?php if (!empty($logs)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Log Level</th>
                                        <th>Message</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['id']) ?></td>
                                            <td class="log-level <?= strtolower($log['log_level']) ?>">
                                                <?= htmlspecialchars($log['log_level']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($log['message']) ?></td>
                                            <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($log['created_at']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No logs found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
