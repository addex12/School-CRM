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
/* Adugna Gizaw: Sidebar styles with adugna- prefix, always visible on desktop, responsive, and active highlighting */
.adugna-sidebar {
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
.adugna-sidebar .adugna-sidebar-header {
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
.adugna-sidebar .adugna-sidebar-toggle {
    background: none;
    border: none;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    margin-left: 5px;
    padding: 0.4rem 0.6rem;
    position: fixed;
    top: 10px;
    left: 55px;
    z-index: 300;
}
.adugna-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
}
.adugna-sidebar ul li {
    width: 100%;
}
.adugna-sidebar ul li a {
    display: flex;
    align-items: center;
    padding: 0.6rem 1rem;
    color: #fff;
    text-decoration: none;
    font-size: 0.93rem;
    transition: background 0.15s, color 0.15s;
    border-left: 4px solid transparent;
    font-weight: 500;
    position: relative;
}
.adugna-sidebar ul li a.adugna-active,
.adugna-sidebar ul li a:hover {
    background: #1976d2;
    color: #fff;
    border-left: 4px solid #f1c40f;
}
.adugna-sidebar ul li a i {
    margin-right: 0.7rem;
    font-size: 1rem;
    min-width: 22px;
    text-align: center;
}
.adugna-sidebar .adugna-submenu {
    background: #263043;
    padding-left: 0.5rem;
}
.adugna-sidebar .adugna-submenu li a {
    font-size: 0.89rem;
    padding-left: 2rem;
    border-left: none;
}
.adugna-sidebar .adugna-submenu li a.adugna-active,
.adugna-sidebar .adugna-submenu li a:hover {
    background: #215967;
    color: #fff;
}
@media (max-width: 900px) {
    .adugna-sidebar {
        width: 200px;
    }
    .adugna-main {
        margin-left: 200px;
    }
}
@media (max-width: 600px) {
    .adugna-sidebar {
        width: 100vw;
        left: -100vw;
        transition: left 0.2s;
    }
    .adugna-sidebar.open {
        left: 0;
    }
    .adugna-main {
        margin-left: 0 !important;
        padding-left: 0 !important;
        position: relative;
        z-index: 1;
    }
    .adugna-sidebar .adugna-sidebar-toggle {
        top: 15px;
        left: 15px;
    }
}
.adugna-main {
    margin-left: 240px;
    padding: 20px;
    transition: margin-left 0.2s;
    background: #f4f6fa;
    min-height: 100vh;
}
</style>
<div class="adugna-sidebar" id="adugnaSidebar">
    <div class="adugna-sidebar-header">
        <span>Admin</span>
        <button class="adugna-sidebar-toggle" id="adugnaSidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <ul>
        <?php
        // Helper to render menu recursively with adugna-active class for current page
        function adugna_renderSidebarMenu($items) {
            $currentPage = basename($_SERVER['PHP_SELF']);
            foreach ($items as $item) {
                $hasSub = isset($item['items']) && is_array($item['items']);
                $icon = isset($item['icon']) ? 'fa-' . $item['icon'] : 'fa-circle';
                $active = (isset($item['link']) && $currentPage === $item['link']) ? 'adugna-active' : '';
                if ($hasSub) {
                    echo '<li>';
                    echo '<a href="#" class="adugna-sidebar-parent"><i class="fas ' . $icon . '"></i> <span>' . htmlspecialchars($item['title'] ?? '') . '</span> <i class="fas fa-chevron-down" style="margin-left:auto;font-size:0.85em;"></i></a>';
                    echo '<ul class="adugna-submenu" style="display:none;">';
                    adugna_renderSidebarMenu($item['items']);
                    echo '</ul>';
                    echo '</li>';
                } elseif (isset($item['link'])) {
                    echo '<li><a href="' . htmlspecialchars($item['link']) . '" class="' . $active . '"><i class="fas ' . $icon . '"></i> <span>' . htmlspecialchars($item['title']) . '</span></a></li>';
                }
            }
        }
        // Use config if available, else fallback to static menu
        if (!empty($sidebarConfig['menu'])) {
            adugna_renderSidebarMenu($sidebarConfig['menu']);
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
/**
 * Adugna Gizaw: Sidebar toggle and submenu logic.
 * - Sidebar stays open after navigation on desktop.
 * - Highlights the active page.
 * - On mobile, sidebar can be toggled.
 */
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('adugnaSidebar');
    var toggle = document.getElementById('adugnaSidebarToggle');
    var main = document.querySelector('.adugna-main');
    // Sidebar toggle for mobile
    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (window.innerWidth <= 600) {
            sidebar.classList.toggle('open');
        }
    });
    // Submenu toggle
    document.querySelectorAll('.adugna-sidebar-parent').forEach(function(parent) {
        parent.addEventListener('click', function(e) {
            e.preventDefault();
            var submenu = parent.nextElementSibling;
            if (submenu && submenu.classList.contains('adugna-submenu')) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
    // Highlight active menu on load
    var current = window.location.pathname.split('/').pop();
    document.querySelectorAll('.adugna-sidebar ul li a').forEach(function(link) {
        if (link.getAttribute('href') && link.getAttribute('href').indexOf(current) !== -1) {
            link.classList.add('adugna-active');
        }
    });
    // Keep sidebar open on desktop after navigation
    if (window.innerWidth > 600) {
        sidebar.classList.remove('open');
    }
});
</script>
