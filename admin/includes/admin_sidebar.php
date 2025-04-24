<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
// Sidebar config path
$configPath = __DIR__ . '/sidebar_config.json';
$sidebarConfig = [];
if (file_exists($configPath)) {
    $sidebarConfig = json_decode(file_get_contents($configPath), true);
}
?>
<style>
/* ERPNext/modern sidebar styling */
.admin-sidebar {
    width: 240px;
    background: #222d32;
    color: #fff;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 100;
    transition: width 0.2s;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
}
.admin-sidebar.collapsed {
    width: 60px;
}
.admin-sidebar .sidebar-header {
    padding: 1.2rem 1.5rem;
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
    background: #1976d2;
    letter-spacing: 1px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.admin-sidebar .sidebar-toggle {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.3rem;
    cursor: pointer;
    margin-left: 10px;
}
.admin-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
}
.admin-sidebar ul li {
    width: 100%;
}
.admin-sidebar ul li a {
    display: flex;
    align-items: center;
    padding: 0.85rem 1.5rem;
    color: #fff;
    text-decoration: none;
    font-size: 1rem;
    transition: background 0.15s, color 0.15s;
    border-left: 4px solid transparent;
    font-weight: 500;
}
.admin-sidebar ul li a.active,
.admin-sidebar ul li a:hover {
    background: #1976d2;
    color: #fff;
    border-left: 4px solid #fff;
}
.admin-sidebar ul li a i {
    margin-right: 1rem;
    font-size: 1.1em;
    min-width: 22px;
    text-align: center;
}
.admin-sidebar.collapsed ul li a span {
    display: none;
}
.admin-sidebar.collapsed .sidebar-header {
    font-size: 1.5rem;
    padding: 1.2rem 0.5rem;
}
.admin-sidebar.collapsed ul li a {
    justify-content: center;
    padding: 0.85rem 0.5rem;
}
.admin-sidebar .submenu {
    background: #263043;
    padding-left: 0.5rem;
}
.admin-sidebar .submenu li a {
    font-size: 0.97em;
    padding-left: 2.5rem;
    border-left: none;
}
.admin-sidebar .submenu li a.active,
.admin-sidebar .submenu li a:hover {
    background: #215967;
    color: #fff;
}
@media (max-width: 900px) {
    .admin-sidebar {
        position: absolute;
        z-index: 200;
        min-height: 100%;
    }
    .admin-main {
        margin-left: 0 !important;
    }
}
.admin-main {
    margin-left: 240px;
    transition: margin-left 0.2s;
}
.admin-sidebar.collapsed ~ .admin-main {
    margin-left: 60px;
}
</style>
<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <span>Admin</span>
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <ul>
        <?php
        // Helper to render menu recursively
        function renderSidebarMenu($items, $current = '') {
            foreach ($items as $item) {
                $hasSub = isset($item['items']) && is_array($item['items']);
                $icon = isset($item['icon']) ? 'fa-' . $item['icon'] : 'fa-circle';
                // Fix: Only check 'link' if it exists and is not empty
                $active = (isset($item['link']) && basename($_SERVER['PHP_SELF']) === $item['link']) ? 'active' : '';
                if ($hasSub) {
                    echo '<li>';
                    echo '<a href="#" class="sidebar-parent"><i class="fas ' . $icon . '"></i> <span>' . htmlspecialchars($item['title'] ?? '') . '</span> <i class="fas fa-chevron-down" style="margin-left:auto;font-size:0.85em;"></i></a>';
                    echo '<ul class="submenu" style="display:none;">';
                    renderSidebarMenu($item['items'], $current);
                    echo '</ul>';
                    echo '</li>';
                } elseif (isset($item['link'])) {
                    echo '<li><a href="' . htmlspecialchars($item['link']) . '" class="' . $active . '"><i class="fas ' . $icon . '"></i> <span>' . htmlspecialchars($item['title']) . '</span></a></li>';
                }
                // If neither 'items' nor 'link', skip rendering this item
            }
        }
        // Use config if available, else fallback to static menu
        if (!empty($sidebarConfig['menu'])) {
            renderSidebarMenu($sidebarConfig['menu']);
        } else {
            // Fallback static menu (minimal)
            ?>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="active_users.php"><i class="fas fa-users"></i> <span>Active Users</span></a></li>
            <li><a href="add_users.php"><i class="fas fa-user-plus"></i> <span>Add Users</span></a></li>
            <li><a href="roles.php"><i class="fas fa-user-tag"></i> <span>Roles</span></a></li>
            <li><a href="settings.php"><i class="fas fa-cogs"></i> <span>Settings</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            <?php
        }
        ?>
    </ul>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('adminSidebar');
    var toggle = document.getElementById('sidebarToggle');
    var main = document.querySelector('.admin-main');
    // Helper to set collapsed state
    function setSidebarCollapsed(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            if (main) main.style.marginLeft = '10px';
            localStorage.setItem('sidebar-collapsed', '1');
        } else {
            sidebar.classList.remove('collapsed');
            if (main) main.style.marginLeft = '240px';
            localStorage.setItem('sidebar-collapsed', '0');
        }
    }
    // Initial state
    setSidebarCollapsed(localStorage.getItem('sidebar-collapsed') === '1');

    // Always toggle collapsed state on button click
    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        var isCollapsed = sidebar.classList.contains('collapsed');
        setSidebarCollapsed(!isCollapsed);
    });

    // Also allow expanding sidebar by clicking anywhere on the collapsed sidebar (optional UX)
    sidebar.addEventListener('click', function(e) {
        if (sidebar.classList.contains('collapsed') && e.target === sidebar) {
            setSidebarCollapsed(false);
        }
    });

    // Submenu toggle
    document.querySelectorAll('.sidebar-parent').forEach(function(parent) {
        parent.addEventListener('click', function(e) {
            e.preventDefault();
            var submenu = parent.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
});
</script>
