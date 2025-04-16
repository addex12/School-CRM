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
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="openChatInterface()">
                <i class="fas fa-comments"></i>
                <span>Messages</span>
                <span class="badge badge-danger"></span>
            </a>
        </li>
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
    // Sidebar hamburger toggle for mobile
    var sidebar = document.getElementById('adminSidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 700 && sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('open');
            }
        }
    });
})();
</script>

    </script>
</body>
</html>
