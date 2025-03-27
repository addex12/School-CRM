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
            <div class="logo-container">
                <i class="fas fa-shield-alt logo-icon"></i>
                <h2 class="logo-text">Admin Panel</h2>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <div class="sidebar-content">
            <ul class="sidebar-menu">
                <?php foreach ($sidebarConfig['menu'] as $item): ?>
                    <?php if (isset($item['items'])): // Category with subitems ?>
                        <li class="menu-category">
                            <div class="category-header" data-toggle="collapse" data-target="#<?= $item['id'] ?>">
                                <i class="fas fa-<?= $item['icon'] ?> category-icon"></i>
                                <span class="category-text"><?= $item['title'] ?></span>
                                <i class="fas fa-chevron-down collapse-icon"></i>
                            </div>
                            <ul class="submenu collapse" id="<?= $item['id'] ?>">
                                <?php foreach ($item['items'] as $subitem): ?>
                                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == $subitem['link'] ? 'active' : '' ?>">
                                        <a href="<?= $subitem['link'] ?>">
                                            <i class="fas fa-<?= $subitem['icon'] ?>"></i>
                                            <span><?= $subitem['title'] ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: // Single menu item ?>
                        <li class="menu-item <?= basename($_SERVER['PHP_SELF']) == $item['link'] ? 'active' : '' ?>">
                            <a href="<?= $item['link'] ?>" class="menu-link">
                                <i class="fas fa-<?= $item['icon'] ?> menu-icon"></i>
                                <span class="menu-text"><?= $item['title'] ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

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
