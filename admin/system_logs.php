<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "System Logs";

// Check if the system_logs table exists before querying
$tableExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    $tableExists = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $tableExists = false;
}

$logs = [];
$error = '';
if ($tableExists) {
    try {
        $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 100");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Failed to fetch system logs: " . $e->getMessage();
    }
} else {
    $error = "System logs table does not exist. Please contact the administrator to create the 'system_logs' table.";
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
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.2rem 1.2rem;
            margin-bottom: 1.2rem;
            max-width: 900px;
            border: 1px solid #e5e7eb;
        }
        .adugna-card h2 {
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 0.9rem;
            font-size: 1.1rem;
            letter-spacing: 0.01em;
        }
        .adugna-table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            padding: 8px 8px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 0.93rem;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2563eb;
        }
        tr:hover {
            background: #f4f8fb;
        }
        .alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
        }
        @media (max-width: 900px) {
            .adugna-card { padding: 0.7rem; }
        }
        @media (max-width: 600px) {
            .adugna-card { padding: 0.4rem; }
            th, td { font-size: 0.85em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <header class="adugna-admin-header">
                <h1><i class="fas fa-file-alt"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="adugna-card">
                <h2>System Logs</h2>
                <?php if ($error): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php elseif (!empty($logs)): ?>
                    <div class="adugna-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['id']) ?></td>
                                        <td><?= htmlspecialchars($log['user_id']) ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['description']) ?></td>
                                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No system logs found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
