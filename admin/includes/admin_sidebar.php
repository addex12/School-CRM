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
    background: linear-gradient(180deg, #222d32 0%, #34495e 100%);
    color: #fff;
    width: 250px;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 200;
    padding-top: 30px;
    box-shadow: 2px 0 8px rgba(44,62,80,0.07);
    transition: width 0.2s, left 0.2s;
    display: flex;
    flex-direction: column;
}
.admin-sidebar ul { list-style: none; padding: 0; margin: 0; }
.admin-sidebar li { margin-bottom: 12px; }
.admin-sidebar a {
    color: #b8c7ce;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 10px 22px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 500;
    transition: background 0.18s, color 0.18s;
    gap: 10px;
}
.admin-sidebar li.active a, .admin-sidebar a:hover {
    background: #1a2226;
    color: #f1c40f;
}
.admin-sidebar i { margin-right: 10px; font-size: 1.2em; }
.sidebar-toggle {
    display: none;
}
@media (max-width: 900px) {
    .admin-sidebar {
        width: 60px;
        padding-top: 10px;
    }
    .admin-sidebar a {
        padding: 10px 10px;
        font-size: 0;
    }
    .admin-sidebar a .menu-text, .admin-sidebar a .category-text {
        display: none;
    }
    .admin-sidebar i {
        margin-right: 0;
        font-size: 1.3em;
    }
    .sidebar-toggle {
        display: block;
        position: fixed;
        top: 12px;
        left: 12px;
        background: #1a2226;
        color: #fff;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
        z-index: 300;
        border: none;
    }
}
@media (max-width: 600px) {
    .admin-sidebar {
        left: -250px;
        width: 220px;
        transition: left 0.2s;
    }
    .admin-sidebar.open {
        left: 0;
    }
}
</style>
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>
<aside class="admin-sidebar" id="adminSidebar">
    <ul>
        <?php foreach ($sidebarItems as $item): ?>
            <?php if (isset($item['items'])): // Category with subitems ?>
                <li class="menu-category">
                    <div class="category-header" data-toggle="collapse" data-target="#<?= $item['id'] ?>">
                        <i class="fas fa-<?= $item['icon'] ?> category-icon"></i>
                        <span class="category-text"><?= $item['title'] ?></span>
                        <i class="fas fa-chevron-down collapse-icon"></i>
                    </div>
                    <ul class="submenu" id="<?= $item['id'] ?>"<?php
                        // If any subitem is active, open this submenu
                        $active = false;
                        foreach ($item['items'] as $subitem) {
                            if (basename($_SERVER['PHP_SELF']) == $subitem['link']) {
                                $active = true;
                                break;
                            }
                        }
                        echo $active ? ' style="display:block"' : '';
                    ?>>
                        <?php foreach ($item['items'] as $subitem): ?>
                            <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == $subitem['link'] ? 'active' : '' ?>">
                                <a href="<?= $subitem['link'] ?>">
                                    <i class="fas fa-<?= $subitem['icon'] ?>"></i>
                                    <span class="menu-text"><?= $subitem['title'] ?></span>
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
(function() {
    // Submenu toggle
    var headers = document.querySelectorAll('.category-header');
    headers.forEach(function(header) {
        header.addEventListener('click', function(e) {
            var targetId = header.getAttribute('data-target');
            var submenu = document.getElementById(targetId.replace('#',''));
            var icon = header.querySelector('.collapse-icon');
            // Close all submenus except this one
            document.querySelectorAll('.submenu').forEach(function(sm) {
                if (sm !== submenu) {
                    sm.classList.remove('open');
                    sm.style.display = 'none';
                }
            });
            document.querySelectorAll('.collapse-icon').forEach(function(ic) {
                if (ic !== icon) ic.classList.remove('fa-chevron-up');
                if (ic !== icon) ic.classList.add('fa-chevron-down');
            });
            if (submenu) {
                var isOpen = submenu.classList.contains('open');
                if (isOpen) {
                    submenu.classList.remove('open');
                    submenu.style.display = 'none';
                    if(icon) { icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); }
                } else {
                    submenu.classList.add('open');
                    submenu.style.display = 'block';
                    if(icon) { icon.classList.add('fa-chevron-up'); icon.classList.remove('fa-chevron-down'); }
                }
            }
        });
    });
    // On page load, ensure only submenu with .submenu-item.active is open
    document.querySelectorAll('.submenu').forEach(function(sm) {
        var active = sm.querySelector('.submenu-item.active');
        if (active) {
            sm.classList.add('open');
            sm.style.display = 'block';
            var chevron = sm.parentElement.querySelector('.collapse-icon');
            if (chevron) {
                chevron.classList.add('fa-chevron-up');
                chevron.classList.remove('fa-chevron-down');
            }
        } else {
            sm.classList.remove('open');
            sm.style.display = 'none';
        }
    });
    // Sidebar hamburger toggle for mobile/tablet
    var sidebar = document.getElementById('adminSidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });
    }
    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 600 && sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('open');
            }
        }
    });
})();
</script>
