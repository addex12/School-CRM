<?php
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = "Announcements";
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
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .dashboard-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            margin-bottom: 2rem;
            padding: 2rem 2.5rem;
        }
        .ann-header { font-size: 1.5rem; color: #215967; font-weight: 700; margin-bottom: 1.5rem; }
        .ann-empty { color: #888; font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="dashboard-section">
                <div class="ann-header"><i class="fas fa-bullhorn"></i> Announcements</div>
                <div class="ann-empty">
                    No announcements yet.<br>
                    <span style="font-size:1.2em;">Coming soon...</span>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>