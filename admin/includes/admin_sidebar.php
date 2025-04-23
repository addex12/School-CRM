<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
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
/* ERPNext/Frappe inspired sidebar styles */
.admin-sidebar {
    background: #f5f7fa;
    color: #222d32;
    width: 260px;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 200;
    padding-top: 0;
    box-shadow: 2px 0 8px rgba(44,62,80,0.07);
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e5e7eb;
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
}
.sidebar-header {
    padding: 1.5rem 2rem 1rem 2rem;
    font-size: 1.3rem;
    font-weight: 700;
    color: #215967;
    letter-spacing: 1px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
}
.admin-sidebar ul { list-style: none; padding: 0; margin: 0; }
.admin-sidebar li { margin-bottom: 0; }
.admin-sidebar a {
    color: #215967;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 0.85rem 2rem;
    border-radius: 0;
    font-size: 1rem;
    font-weight: 500;
    transition: background 0.18s, color 0.18s;
    gap: 12px;
    border-left: 3px solid transparent;
    letter-spacing: 0.01em;
}
.admin-sidebar li.active > a,
.admin-sidebar a:hover,
.admin-sidebar .submenu-item.active > a {
    background: #e2efda;
    color: #215967;
    border-left: 3px solid #3b82f6;
}
.admin-sidebar .menu-category > .category-header {
    padding: 0.85rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    color: #215967;
    cursor: pointer;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 10px;
    user-select: none;
}
.admin-sidebar .category-header .collapse-icon {
    margin-left: auto;
    font-size: 1em;
    transition: transform 0.2s;
}
.admin-sidebar .category-header.open .collapse-icon {
    transform: rotate(180deg);
}
.admin-sidebar .submenu {
    background: #f8fafc;
    padding-left: 0;
    border-left: 2px solid #e5e7eb;
    display: none;
}
.admin-sidebar .submenu.open { display: block; }
.admin-sidebar .submenu-item a {
    padding: 0.7rem 2.5rem;
    font-size: 0.97rem;
    color: #215967;
    border-left: 3px solid transparent;
}
.admin-sidebar .submenu-item.active > a,
.admin-sidebar .submenu-item a:hover {
    background: #e2efda;
    color: #2563eb;
    border-left: 3px solid #3b82f6;
}
.admin-sidebar i {
    font-size: 1.15em;
    min-width: 20px;
    text-align: center;
}
.sidebar-toggle {
    display: none;
}
@media (max-width: 900px) {
    .admin-sidebar {
        width: 60px;
        padding-top: 0;
    }
    .sidebar-header { display: none; }
    .admin-sidebar a, .admin-sidebar .category-header {
        padding: 0.85rem 0.7rem;
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
        background: #fff;
        color: #215967;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
        z-index: 300;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(44,62,80,0.07);
    }
}
@media (max-width: 600px) {
    .admin-sidebar {
        left: -260px;
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
    <div class="sidebar-header">
        <i class="fas fa-graduation-cap"></i> School CRM
    </div>
    <ul class="sidebar-menu">
        <?php foreach ($sidebarItems as $item): ?>
            <?php if (isset($item['items'])): // Category with subitems ?>
                <li class="menu-category">
                    <div class="category-header<?= (isset($item['open']) && $item['open']) ? ' open' : '' ?>" data-toggle="collapse" data-target="#<?= $item['id'] ?>">
                        <i class="fas fa-<?= $item['icon'] ?> category-icon"></i>
                        <span class="category-text"><?= $item['title'] ?></span>
                        <i class="fas fa-chevron-down collapse-icon"></i>
                    </div>
                    <ul class="submenu" id="<?= $item['id'] ?>"<?php
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
// ERPNext/Frappe inspired sidebar JS
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
