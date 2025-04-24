<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "System Logs";

// Pagination setup
$perPage = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Fetch logs
$stmt = $pdo->prepare("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT :offset, :perpage");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perpage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total logs for pagination
$total = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 1.2rem 0.5rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.2rem 1.2rem;
            margin-bottom: 1.2rem;
            max-width: 900px;
            border: 1px solid #e5e7eb;
        }
        .erp-card h2 {
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 0.9rem;
            font-size: 1.1rem;
        }
        .erpnext-btn {
            background: linear-gradient(90deg, #2563eb 0%, #215967 100%);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.35rem 0.9rem;
            font-size: 0.97em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            margin-bottom: 0.7rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
        }
        .erpnext-btn i {
            font-size: 0.97em;
        }
        .erpnext-btn:hover, .erpnext-btn:focus {
            background: linear-gradient(90deg, #215967 0%, #2563eb 100%);
            box-shadow: 0 2px 8px rgba(44,62,80,0.12);
        }
        .admin-header {
            margin-bottom: 1.2rem;
            border-bottom: 1.5px solid #e5e7eb;
            padding-bottom: 0.7rem;
        }
        .admin-header h1 {
            color: #2563eb;
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: 0.01em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .admin-header i {
            font-size: 1.1em;
        }
        @media (max-width: 900px) {
            .admin-main { margin-left: 70px; padding: 0.7rem 0.3rem; }
            .erp-card { padding: 0.7rem; }
        }
        @media (max-width: 600px) {
            .admin-main { margin-left: 0; padding: 0.3rem; }
            .erp-card { padding: 0.4rem; }
            .admin-header h1 { font-size: 1rem; }
            .erp-card h2 { font-size: 1em; }
        }
        .erp-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.2rem;
            background: #fff;
        }
        .erp-table th, .erp-table td {
            padding: 0.5em 0.7em;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 0.95rem;
        }
        .erp-table th {
            background: #f3f6fa;
            color: #215967;
            font-weight: 600;
        }
        .erp-table tr:hover {
            background: #f5faff;
        }
        .erp-pagination {
            display: flex;
            gap: 0.3em;
            align-items: center;
        }
        .erp-pagination a, .erp-pagination span {
            padding: 0.2em 0.6em;
            border-radius: 4px;
            background: #f3f6fa;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            transition: background 0.15s;
            font-size: 0.95em;
        }
        .erp-pagination .active, .erp-pagination a:hover {
            background: #2563eb;
            color: #fff;
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
            <div class="erp-card">
                <h2>System Logs</h2>
                <p>Below are all user, admin, and system activities. Every action is logged for traceability.</p>
                <div style="overflow-x:auto;">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($logs): ?>
                            <?php foreach ($logs as $i => $log): ?>
                                <tr>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td><?= htmlspecialchars($log['username'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['role'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No logs found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="erp-pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p == $page): ?>
                            <span class="active"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <a href="audit_trail.php" class="erpnext-btn"><i class="fas fa-history"></i> Audit Trail</a>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
