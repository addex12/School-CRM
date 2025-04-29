<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
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
    <!-- Adugna Gizaw: Responsive, compact, ERPNext-inspired audit log page with adugna- prefix -->
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/audit_log.js" defer></script>
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }

        .adugna-main {
            min-height: 100vh;
            background: #f7f9fb;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        .adugna-content-container {
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 20px;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header {
            font-size: 1.13em;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 10px;
            letter-spacing: 0.01em;
        }
        .adugna-card-body {
            font-size: 0.97em;
            color: #444;
        }
        .adugna-search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 1em;
            flex-wrap: wrap;
            align-items: center;
        }
        .adugna-search-input {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
            width: 220px;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 4px 12px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 1em;
        }
        .adugna-btn:hover {
            background: #145ea8;
        }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover {
            background: #d0e2fa;
        }
        .adugna-btn-sm {
            padding: 2px 7px;
            font-size: 0.93em;
            border-radius: 3px;
        }
        .adugna-audit-log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.97em;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-audit-log-table th, .adugna-audit-log-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-audit-log-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 600;
            font-size: 0.98em;
        }
        .adugna-audit-log-table tr:hover {
            background-color: #f8f9fa;
        }
        .adugna-pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
            gap: 2px;
            margin-top: 1em;
            align-items: center;
        }
        .adugna-page-link {
            display: block;
            padding: 0.4rem 0.6rem;
            margin-left: -1px;
            line-height: 1.25;
            color: #1976d2;
            background-color: #fff;
            border: 1px solid #dee2e6;
            text-decoration: none;
            font-size: 0.95em;
            border-radius: 3px;
            transition: background 0.15s;
        }
        .adugna-page-link.active, .adugna-page-link:focus {
            background-color: #1976d2;
            border-color: #1976d2;
            color: white;
        }
        .adugna-page-link:hover {
            background-color: #e9ecef;
            color: #1976d2;
        }
        .adugna-alert-info {
            background: #f5f7fa;
            color: #1976d2;
            border: 1px solid #e3eafc;
            border-radius: 5px;
            padding: 12px 16px;
            margin: 18px 0;
            font-size: 0.98em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        @media (max-width: 1100px) {
            .adugna-content-container {
                max-width: 99vw;
                margin: 18px 2vw 0 2vw;
                padding: 10px 4px 18px 4px;
            }
        }
        @media (max-width: 900px) {
            .adugna-card, .adugna-content-container {
                padding: 0.7rem 0.5rem 1rem 0.5rem;
            }
            .adugna-audit-log-table th, .adugna-audit-log-table td {
                padding: 5px 4px;
                font-size: 0.95em;
            }
        }
        @media (max-width: 600px) {
            .adugna-card, .adugna-content-container {
                padding: 0.5rem 0.2rem 0.7rem 0.2rem;
            }
            .adugna-header-title {
                font-size: 1.1em;
            }
            .adugna-search-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Adugna Gizaw: Main admin dashboard layout, content and screen size aware -->
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-content-container">
                <header class="adugna-header-title">
                    <?= htmlspecialchars($pageTitle) ?>
                </header>
                <?php include 'includes/alerts.php'; ?>
                <div class="adugna-card">
                    <div class="adugna-card-header">Audit Logs</div>
                    <div class="adugna-card-body">
                        <div class="adugna-search-container">
                            <input type="text" id="audit-log-search" placeholder="Search logs..." class="adugna-search-input">
                            <button id="export-audit-log" class="adugna-btn adugna-btn-sm"><i class="fas fa-download"></i> Export CSV</button>
                        </div>
                        <?php if (count($auditLogs) > 0): ?>
                            <table class="adugna-audit-log-table">
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
                                        <td>
                                            <?php
                                            $action = $log['action'];
                                            // Example: Add more system log types and descriptions
                                            $actionDescriptions = [
                                                'login' => 'User Login',
                                                'logout' => 'User Logout',
                                                'create_user' => 'User Created',
                                                'update_user' => 'User Updated',
                                                'delete_user' => 'User Deleted',
                                                'create_role' => 'Role Created',
                                                'update_role' => 'Role Updated',
                                                'delete_role' => 'Role Deleted',
                                                'create_announcement' => 'Announcement Created',
                                                'update_announcement' => 'Announcement Updated',
                                                'delete_announcement' => 'Announcement Deleted',
                                                'bulk_import' => 'Bulk Import',
                                                'export_data' => 'Data Exported',
                                                'system_error' => 'System Error',
                                                'db_backup' => 'Database Backup',
                                                'settings_update' => 'Settings Updated',
                                                'permission_change' => 'Permission Changed',
                                                'reset_password' => 'Password Reset',
                                                'login_failed' => 'Failed Login Attempt',
                                                'access_denied' => 'Access Denied',
                                                // ...add more as needed...
                                            ];
                                            echo isset($actionDescriptions[$action])
                                                ? htmlspecialchars($actionDescriptions[$action])
                                                : htmlspecialchars($action);
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($log['details'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <!-- Adugna Gizaw: Pagination for audit logs -->
                            <div class="adugna-pagination" style="justify-content:center;">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>" class="adugna-page-link"><i class="fas fa-chevron-left"></i> Prev</a>
                                <?php endif; ?>
                                <span class="adugna-page-link active">Page <?= $page ?> of <?= $totalPages ?></span>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>" class="adugna-page-link">Next <i class="fas fa-chevron-right"></i></a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="adugna-alert-info">
                                <i class="fas fa-info-circle"></i> No audit logs found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>