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
$pageTitle = "Permissions";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, outstanding, responsive UI.
         * All cards, buttons, and messages use adugna- prefix for branding/patent.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 800px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.25em;
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
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #2563eb 0%, #215967 100%);
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.32rem 0.9rem;
            font-size: 0.92em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            margin-bottom: 1rem;
            box-shadow: 0 1px 4px rgba(25,118,210,0.07);
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
            min-height: 28px;
        }
        .adugna-btn i { font-size: 0.92em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #215967 0%, #2563eb 100%);
            box-shadow: 0 2px 8px rgba(25,118,210,0.12);
        }
        .adugna-header {
            margin-bottom: 2rem;
            border-bottom: 1.5px solid #e5e7eb;
            padding-bottom: 1rem;
        }
        .adugna-header h1 {
            color: #2563eb;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.01em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.7em;
        }
        @media (max-width: 900px) {
            .adugna-main-content { padding: 1rem; }
            .adugna-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.5rem; }
            .adugna-card { padding: 0.7rem; }
            .adugna-header h1 { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <header class="adugna-header">
                <h1><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="adugna-card">
                <h2 style="color:#2563eb;font-weight:600;margin-bottom:1.2rem;font-size:1.08em;">Manage Permissions</h2>
                <p>This is a placeholder for the permissions management interface. Implement permission assignment and management here.</p>
                <a href="manage_roles.php" class="adugna-btn"><i class="fas fa-user-shield"></i> Manage Roles</a>
                <a href="user_permissions.php" class="adugna-btn"><i class="fas fa-user-lock"></i> User Permissions</a>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
