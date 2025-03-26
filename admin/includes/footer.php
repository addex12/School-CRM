<?php
require_once '../../includes/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Responsive and visually appealing styles */
        .admin-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .admin-header, .admin-footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 1rem;
            text-align: center;
        }
        .admin-header .branding h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        .admin-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .admin-nav a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .admin-nav a.active, .admin-nav a:hover {
            background-color: #34495e;
        }
        .admin-main {
            flex: 1;
            padding: 1rem;
        }
        .admin-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .footer-section {
            flex: 1;
            min-width: 200px;
        }
        .footer-section h4 {
            margin-bottom: 0.5rem;
        }
        .footer-section a {
            color: #ecf0f1;
            text-decoration: none;
        }
        .footer-section a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-container"></div>
        <header class="admin-header">
            <div class="branding">
                <h1>School Survey System</h1>
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
        <footer class="admin-footer">
            <div class="footer-section">
                <p>&copy; <?php echo date('Y'); ?> School Survey System. All rights reserved.</p>
            </div>
            <div class="footer-section developer-info">
                <h4>Developer</h4>
                <p><strong>Adugna Gizaw</strong></p>
                <p>Email: <a href="mailto:gizawadugna@gmail.com">gizawadugna@gmail.com</a></p>
                <p></p>
                    <a href="https://www.linkedin.com/in/eleganceict" target="_blank">LinkedIn</a> |
                    <a href="https://twitter.com/eleganceict1" target="_blank">Twitter</a> |
                    <a href="https://github.com/addex12" target="_blank">GitHub</a>
                </p>
            </div>
        </footer>
    </div>
</body>
</html>