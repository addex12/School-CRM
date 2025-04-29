<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Set timezone for audit trail
date_default_timezone_set('Africa/Nairobi');
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = "Audit Trail";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .adugna-main {
            min-height: 100vh;
            background: #f7f9fb;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        .adugna-content-container {
            max-width: 900px;
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
        .adugna-card h2 {
            color: #1976d2;
            font-weight: 600;
            margin-bottom: 1.2rem;
            font-size: 1.13em;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 0.7em;
        }
        .adugna-btn i {
            font-size: 1em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
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
        .adugna-admin-header {
            margin-bottom: 2rem;
            border-bottom: 1.5px solid #e5e7eb;
            padding-bottom: 1rem;
        }
        .adugna-admin-header h1 {
            color: #1976d2;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 0.01em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.7em;
        }
        @media (max-width: 1100px) {
            .adugna-content-container {
                max-width: 99vw;
                margin: 18px 2vw 0 2vw;
                padding: 10px 4px 18px 4px;
            }
        }
        @media (max-width: 900px) {
            .adugna-main, .adugna-content-container {
                padding: 0.7rem 0.5rem 1rem 0.5rem;
            }
            .adugna-card { padding: 1rem; }
            .adugna-admin-header { padding-bottom: 0.7rem; }
        }
        @media (max-width: 600px) {
            .adugna-main, .adugna-content-container {
                padding: 0.5rem 0.2rem 0.7rem 0.2rem;
            }
            .adugna-card { padding: 0.7rem; }
            .adugna-admin-header h1 { font-size: 1.2rem; }
            .adugna-header-title { font-size: 1.1em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <div class="adugna-content-container">
                <header class="adugna-admin-header">
                    <h1><i class="fas fa-history"></i> <?= htmlspecialchars($pageTitle) ?></h1>
                </header>
                <div class="adugna-card">
                    <h2><i class="fas fa-history"></i> Audit Trail</h2>
                    <p>This is a placeholder for the audit trail. Show user actions and changes here for accountability.</p>
                    <div style="display:flex;gap:0.7em;flex-wrap:wrap;">
                        <a href="system_logs.php" class="adugna-btn adugna-btn-secondary"><i class="fas fa-file-alt"></i> System Logs</a>
                        <!-- Adugna Gizaw: Add Audit Log button, compact and consistent -->
                        <a href="audit_log.php" class="adugna-btn adugna-btn-sm"><i class="fas fa-clipboard-list"></i> Audit Log</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
