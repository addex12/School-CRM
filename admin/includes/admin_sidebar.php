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
$unread = isset($ADMIN_UNREAD_MESSAGES) ? (int)$ADMIN_UNREAD_MESSAGES : 0;
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
    width: 90px;
}
.admin-sidebar .sidebar-header {
    padding: 1rem 1rem;
    font-size: 1rem;
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
    font-size: 1rem; /* Increased font size for better visibility */
    cursor: pointer;
    margin-left: 5px;
    padding: 0.4rem 0.6rem; /* Smaller padding */
    position: fixed; /* Ensure it stays visible */
    top: 10px; /* Adjust position */
    left: 55px; /* Adjust position */
    z-index: 300; /* Ensure it is above other elements */
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
    padding: 0.6rem 1rem; /* Smaller padding */
    color: #fff;
    text-decoration: none;
    font-size: 0.9rem; /* Reduced font size */
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
    margin-right: 0.8rem; /* Reduced margin */
    font-size: 1rem; /* Adjusted icon size */
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
    font-size: 0.85rem; /* Smaller font size for submenu */
    padding-left: 2rem; /* Adjusted padding */
    border-left: none;
}
.admin-sidebar .submenu li a.active,
.admin-sidebar .submenu li a:hover {
    background: #215967;
    color: #fff;
}
@media (max-width: 900px) {
    .admin-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        min-height: 100vh;
        width: 200px;
        z-index: 200;
        transition: left 0.2s, width 0.2s;
    }
    .admin-sidebar.collapsed {
        width: 90px;
    }
    .admin-main {
        margin-left: 200px;
    }
    .admin-sidebar.collapsed ~ .admin-main,
    body .admin-sidebar.collapsed + .admin-main {
        margin-left: 60px;
    }
}
@media (max-width: 600px) {
    .admin-sidebar {
        width: 100vw;
        left: -100vw;
        transition: left 0.2s;
    }
    .admin-sidebar.open {
        left: 0;
    }
}
/* Adjust main content to account for sidebar */
.admin-main {
    transition: margin-left 0.2s;
    margin-left: 260px; /* Default sidebar width */
}

@media (max-width: 900px) {
    .admin-main {
        margin-left: 0 !important;
        padding-left: 0 !important;
        position: relative; /* Ensure content adjusts properly */
        z-index: 1; /* Ensure content is above the sidebar */
    }
    .admin-sidebar .sidebar-toggle {
        top: 15px; /* Adjust for smaller screens */
        left: 15px; /* Adjust for smaller screens */
    }
}
.admin-main {
    margin-left: 240px;
    padding: 20px;
    transition: margin-left 0.2s;
    background: #f4f6fa;
    min-height: 100vh;
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
            <li>
                <a href="messages.php">
                    <i class="fas fa-envelope"></i>
                    Messages
                    <?php if ($unread > 0): ?>
                        <span style="background:#e74c3c;color:#fff;border-radius:50%;padding:2px 7px;font-size:0.85em;font-weight:600;margin-left:6px;">
                            <?= $unread ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            <?php
        }
        ?>
    </ul>
</div>
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
    var mainContent = document.querySelector('.admin-main');
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
            if (window.innerWidth > 600) {
                mainContent.style.marginLeft = sidebar.classList.contains('open') ? '260px' : '60px';
            } else {
                mainContent.style.marginLeft = sidebar.classList.contains('open') ? '260px' : '0';
            }
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
