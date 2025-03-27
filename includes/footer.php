<?php
require_once 'auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="branding">
                <h1>School CRM System</h1>
            </div>
            <nav class="admin-nav">
                <a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
                <a href="surveys.php" <?php echo basename($_SERVER['PHP_SELF']) == 'surveys.php' ? 'class="active"' : '' ?>>Surveys</a>
                <a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : '' ?>>Users</a>
                <a href="results.php" <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'class="active"' : '' ?>>Results</a>
                <a href="../../logout.php" class="logout">Logout</a>
            </nav>
        </header>
        <main class="admin-main"></main>
        <div class="admin-footer">
            <p>&copy; <?php echo date('Y'); ?> School Survey System. All rights reserved.</p>
            <p>Developed by <a href="https://github.com/addex12" target="_blank">Adugna Gizaw</a></p>
        </div>
    </div>
</body>
</html>