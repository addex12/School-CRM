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
<link rel="stylesheet" href="includes/admin_sidebar.css">
<style>
/* Remove inline sidebar styles */
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
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('adminSidebar');
    var toggle = document.getElementById('sidebarToggle');
    var main = document.querySelector('.admin-main');
    // Helper to set collapsed state
    function setSidebarCollapsed(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('open');
            if (main) main.style.marginLeft = window.innerWidth <= 900 ? '60px' : '60px';
            localStorage.setItem('sidebar-collapsed', '1');
        } else {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('open');
            if (main) main.style.marginLeft = window.innerWidth <= 900 ? '200px' : '240px';
            localStorage.setItem('sidebar-collapsed', '0');
        }
    }
    // Initial state
    setSidebarCollapsed(localStorage.getItem('sidebar-collapsed') === '1');

    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        var isCollapsed = sidebar.classList.contains('collapsed');
        // On mobile, toggle open/close instead of collapse
        if (window.innerWidth <= 600) {
            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            } else {
                sidebar.classList.add('open');
            }
        } else {
            setSidebarCollapsed(!isCollapsed);
        }
    });

    // Allow expanding sidebar by clicking the sidebar header when collapsed (desktop only)
    sidebar.querySelector('.sidebar-header').addEventListener('click', function(e) {
        if (window.innerWidth > 600 && sidebar.classList.contains('collapsed')) {
            setSidebarCollapsed(false);
        }
    });

    // Hide sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 600 && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && e.target !== toggle) {
                sidebar.classList.remove('open');
            }
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

    // Adjust main content margin dynamically
    function adjustMainMargin() {
        if (window.innerWidth <= 600) {
            if (sidebar.classList.contains('open')) {
                main.style.marginLeft = sidebar.offsetWidth + 'px';
            } else {
                main.style.marginLeft = '0';
            }
        } else if (sidebar.classList.contains('collapsed')) {
            main.style.marginLeft = '60px';
        } else {
            main.style.marginLeft = window.innerWidth <= 900 ? '200px' : '240px';
        }
    }

    // Update margin on sidebar toggle
    toggle.addEventListener('click', function() {
        adjustMainMargin();
    });

    // Update margin on window resize
    window.addEventListener('resize', adjustMainMargin);

    // Initial adjustment
    adjustMainMargin();
});
</script>
