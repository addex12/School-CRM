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
}
.admin-sidebar .sidebar-toggle {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.3rem;
    cursor: pointer;
    margin: 0.5rem 0 0.5rem 0.5rem;
    align-self: flex-end;
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
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <span>Admin</span>
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="active_users.php"><i class="fas fa-users"></i> <span>Active Users</span></a></li>
        <li><a href="add_users.php"><i class="fas fa-user-plus"></i> <span>Add Users</span></a></li>
        <li><a href="roles.php"><i class="fas fa-user-tag"></i> <span>Roles</span></a></li>
        <li><a href="settings.php"><i class="fas fa-cogs"></i> <span>Settings</span></a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('adminSidebar');
    var toggle = document.getElementById('sidebarToggle');
    var main = document.querySelector('.admin-main');
    // Restore collapsed state from localStorage
    if (localStorage.getItem('sidebar-collapsed') === '1') {
        sidebar.classList.add('collapsed');
        if (main) main.style.marginLeft = '60px';
    }
    toggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        if (sidebar.classList.contains('collapsed')) {
            if (main) main.style.marginLeft = '60px';
            localStorage.setItem('sidebar-collapsed', '1');
        } else {
            if (main) main.style.marginLeft = '240px';
            localStorage.setItem('sidebar-collapsed', '0');
        }
    });
});
</script>
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
