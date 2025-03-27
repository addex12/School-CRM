<?php
// Load sidebar configuration from JSON
$sidebarConfig = json_decode(file_get_contents(__DIR__ . '/sidebar_config.json'), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        .admin-sidebar {
            width: 250px;
            height: 100vh;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            position: fixed;
        }
        
        .sidebar-header {
            margin-bottom: 30px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: bold;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu-category {
            margin-bottom: 10px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            background-color: #1a252f;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        
        .category-header:hover {
            background-color: #0d1216;
        }
        
        .category-icon {
            margin-right: 10px;
        }
        
        .collapse-icon {
            margin-left: auto;
        }
        
        .submenu {
            background-color: #1a252f;
            border-radius: 4px;
            padding-left: 20px;
        }
        
        .submenu-item {
            margin: 5px 0;
        }
        
        .submenu-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 0;
        }
        
        .submenu-item a:hover {
            background-color: #0d1216;
            border-radius: 4px;
        }
        
        .submenu-item.active a {
            background-color: #0d1216;
        }
        
        .menu-item {
            margin-bottom: 10px;
        }
        
        .menu-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 4px;
        }
        
        .menu-item a:hover {
            background-color: #0d1216;
        }
        
        .menu-item.active a {
            background-color: #0d1216;
        }
        
        .sidebar-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #374150;
        }
        
        .logout-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 4px;
        }
        
        .logout-btn:hover {
            background-color: #0d1216;
        }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="users.php">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </li>
            <li>
                <a href="classes.php">
                    <i class="fas fa-chalkboard"></i> Manage Classes
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            </li>
            <li>
                <a href="settings.php">
                    <i class="fas fa-cogs"></i> Settings
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px 0;
        }
        .sidebar-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .sidebar-header h2 {
            font-size: 1.5em;
            color: #ecf0f1;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        .sidebar-menu li {
            margin: 15px 0;
        }
        .sidebar-menu a {
            text-decoration: none;
            color: #ecf0f1;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            padding: 10px 20px;
            transition: background-color 0.3s;
        }
        .sidebar-menu a:hover {
            background-color: #34495e;
        }
        .sidebar-menu i {
            margin-right: 10px;
        }
    </style>
    <!-- Include necessary JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize collapse functionality
            $('.category-header').on('click', function() {
                var targetId = $(this).data('target');
                $(targetId).collapse('toggle');
                
                // Toggle icon
                $(this).find('.collapse-icon').toggleClass('fa-chevron-down fa-chevron-up');
            });
        });
    </script>
</body>
</html>
