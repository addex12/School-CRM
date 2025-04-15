<?php
// Load sidebar configuration from JSON
$configPath = __DIR__ . '/sidebar_config.json';
$sidebarItems = [];
if (file_exists($configPath)) {
    $json = file_get_contents($configPath);
    $data = json_decode($json, true);
    $sidebarItems = isset($data['menu']) ? $data['menu'] : $data;
}
$current = basename($_SERVER['PHP_SELF']);
?>
<style>
.admin-sidebar {
    background: #222d32;
    color: #fff;
    width: 220px;
    min-height: 100vh;
    float: left;
    padding-top: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: width 0.2s;
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
.sidebar-toggle {
    display: none;
}
@media (max-width: 768px) {
    .admin-sidebar {
        width: 60px;
        padding-top: 10px;
    }
    .admin-sidebar a {
        padding: 8px 12px;
    }
    .admin-sidebar i {
        margin-right: 6px;
    }
    .sidebar-toggle {
        display: block;
        position: absolute;
        top: 10px;
        right: 10px;
        background: #1a2226;
        color: #fff;
        padding: 8px;
        border-radius: 4px;
        cursor: pointer;
    }
}
</style>
<aside class="admin-sidebar">
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <ul>
        <?php foreach ($sidebarItems as $item): ?>
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
</aside>

<script>
    $(document).ready(function() {
        // Initialize collapse functionality
        $('.category-header').on('click', function() {
            var targetId = $(this).data('target');
            $(targetId).collapse('toggle');
            
            // Toggle icon
            $(this).find('.collapse-icon').toggleClass('fa-chevron-down fa-chevron-up');
        });
        
        // Toggle sidebar on small screens
        $('#sidebarToggle').on('click', function() {
            $('.admin-sidebar').toggleClass('expanded');
</body>
</html>

                
                // Toggle icon
                $(this).find('.collapse-icon').toggleClass('fa-chevron-down fa-chevron-up');
            });
        });
    </script>
</body>
</html>
