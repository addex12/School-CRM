<?php
$configPath = __DIR__ . '/sidebar_config.json';
$sidebarItems = [];
if (file_exists($configPath)) {
    $json = file_get_contents($configPath);
    $data = json_decode($json, true);
    $sidebarItems = isset($data['menu']) ? $data['menu'] : $data;
}
$current = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.admin-sidebar {
    background: #222d32;
    color: #fff;
    width: 220px;
    min-height: 100vh;
    float: left;
    padding-top: 20px;
}
.admin-sidebar ul { list-style: none; padding: 0; }
.admin-sidebar li { margin-bottom: 18px; }
.admin-sidebar a {
    color: #b8c7ce;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 8px 18px;
    border-radius: 4px;
    transition: background 0.2s;
}
.admin-sidebar li.active a, .admin-sidebar a:hover {
    background: #1a2226;
    color: #fff;
}
.admin-sidebar i { margin-right: 12px; }
</style>
<aside class="admin-sidebar">
    <ul>
        <?php foreach ($sidebarItems as $item): ?>
            <li class="<?= (isset($item['link']) && $current === $item['link']) ? 'active' : '' ?>">
                <a href="<?= htmlspecialchars($item['link'] ?? '#') ?>">
                    <?php if (!empty($item['icon'])): ?><i class="<?= htmlspecialchars($item['icon']) ?>"></i><?php endif; ?>
                    <span><?= htmlspecialchars($item['title']) ?></span>
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
<?php endforeach; ?>

