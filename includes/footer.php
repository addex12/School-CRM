<?php
require_once 'auth.php';

// Sanitize current file name for safe comparison and output
$currentPage = htmlspecialchars(basename($_SERVER['PHP_SELF']));
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
                <a href="dashboard.php" <?= $currentPage === 'dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
                <a href="surveys.php" <?= $currentPage === 'surveys.php' ? 'class="active"' : '' ?>>Surveys</a>
                <a href="users.php" <?= $currentPage === 'users.php' ? 'class="active"' : '' ?>>Users</a>
                <a href="results.php" <?= $currentPage === 'results.php' ? 'class="active"' : '' ?>>Results</a>
                <a href="../../logout.php" class="logout">Logout</a>
            </nav>
        </header>
        <main class="admin-main"></main>
        <div class="admin-footer">
            <p>&copy; <?php echo date('Y'); ?> School CRM System. All rights reserved.</p>
            <p>Developed by <a href="https://github.com/addex12" target="_blank">Adugna Gizaw</a></p>
        </div>
    </div>
</body>
</html>