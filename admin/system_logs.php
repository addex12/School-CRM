<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 *
 * System Logs Page: Displays system logs if table exists, else creates the table automatically.
 * All custom styles use adugna- prefix for patenting.
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "System Logs";

// Check if the system_logs table exists, create if missing
$tableExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    $tableExists = $stmt->rowCount() > 0;
    if (!$tableExists) {
        // Create the system_logs table automatically
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $tableExists = true;
    }
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
    $error = "System logs table could not be created. Please check your database permissions.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: ERPNext-inspired, compact, responsive, adugna- prefix */
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .adugna-main { margin-left: 240px; padding: 1.2rem 0.5rem; }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.1rem 1.1rem;
            margin-bottom: 1.2rem;
            max-width: 900px;
            border: 1px solid #e5e7eb;
            margin-left: auto;
            margin-right: auto;
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
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .adugna-table th, .adugna-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 0.93rem;
        }
        .adugna-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2563eb;
        }
        .adugna-table tr:hover {
            background: #f4f8fb;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #2563eb 0%, #215967 100%);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.28rem 0.8rem;
            font-size: 0.93em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            margin-bottom: 0.7rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
        }
        .adugna-btn i {
            font-size: 0.93em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #215967 0%, #2563eb 100%);
            box-shadow: 0 2px 8px rgba(44,62,80,0.12);
        }
        @media (max-width: 900px) {
            .adugna-main { margin-left: 70px; padding: 0.7rem 0.3rem; }
            .adugna-card { padding: 0.7rem; }
        }
        @media (max-width: 600px) {
            .adugna-main { margin-left: 0; padding: 0.3rem; }
            .adugna-card { padding: 0.4rem; }
            .adugna-card h2 { font-size: 1em; }
            .adugna-table th, .adugna-table td { font-size: 0.85em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <header class="adugna-admin-header">
                <h1 style="color:#2563eb;font-weight:700;"><i class="fas fa-file-alt"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="adugna-card">
                <h2><i class="fas fa-database"></i> System Logs</h2>
                <?php if ($error): ?>
                    <div class="adugna-alert-error"><?= htmlspecialchars($error) ?></div>
                <?php elseif (!empty($logs)): ?>
                    <div class="adugna-table-container">
                        <table class="adugna-table">
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
                    <p style="color:#888;">No system logs found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
