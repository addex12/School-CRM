<div?php
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
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            max-width: 800px;
            border: 1px solid #e5e7eb;
        }
        .erp-card h2 {
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 1.2rem;
            font-size: 1.25rem;
        }
        .erpnext-btn {
            background: linear-gradient(90deg, #2563eb 0%, #215967 100%);
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            margin-bottom: 1rem;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
        }
        .erpnext-btn:hover, .erpnext-btn:focus {
            background: linear-gradient(90deg, #215967 0%, #2563eb 100%);
            box-shadow: 0 2px 8px rgba(44,62,80,0.12);
        }
        .admin-header {
            margin-bottom: 2rem;
            border-bottom: 1.5px solid #e5e7eb;
            padding-bottom: 1rem;
        }
        .admin-header h1 {
            color: #2563eb;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 0.01em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.7em;
        }
        @media (max-width: 900px) {
            .admin-main { margin-left: 70px; padding: 1rem; }
            .erp-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .admin-main { margin-left: 0; padding: 0.5rem; }
            .erp-card { padding: 0.7rem; }
            .admin-header h1 { font-size: 1.2rem; }
        }
    </style>
</head>
<b>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="erp-card">
                <h2>Manage Permissions</h2>
                <p>This is a placeholder for the permissions management interface. Implement permission assignment and management here.</p>
                <a href="manage_roles.php" class="erpnext-btn"><i class="fas fa-user-shield"></i> Manage Roles</a>
                <a href="user_permissions.php" class="erpnext-btn"><i class="fas fa-user-lock"></i> User Permissions</a>
            </div>
        </div>
    </div>
    </div></div></div>
    
    </body>
</body>    <?php include 'includes/footer.php'; ?>

</html>
