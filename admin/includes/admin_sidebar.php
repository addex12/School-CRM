<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
* All custom styles use adugna- prefix for patenting.
* Compact ERPNext-inspired sidebar, outstanding, responsive, and interactive.
* Uses style.css for all styling.
*/
// Sidebar config path
$configPath = __DIR__ . '/sidebar_config.json';
$sidebarConfig = [];
if (file_exists($configPath)) {
    $sidebarConfig = json_decode(file_get_contents($configPath), true);
}
$unread = isset($ADMIN_UNREAD_MESSAGES) ? (int)$ADMIN_UNREAD_MESSAGES : 0;
?>
<!-- Adugna Gizaw: Use style.css for all sidebar and layout styling -->
<link rel="stylesheet" href="../assets/css/style.css">
<div class="adugna-sidebar" id="adugnaSidebar">
    <div class="adugna-logo">
        <i class="fas fa-school"></i> School CRM
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
                    echo '<a href="#" class="adugna-sidebar-link adugna-has-submenu"><span class="adugna-icon"><i class="fas ' . $icon . '"></i></span> <span>' . htmlspecialchars($item['title'] ?? '') . '</span> <span class="adugna-submenu-toggle"><i class="fas fa-chevron-right"></i></span></a>';
                    echo '<ul class="adugna-submenu">';
                    adugna_renderSidebarMenu($item['items']);
                    echo '</ul>';
                    echo '</li>';
                } elseif (isset($item['link'])) {
                    echo '<li><a href="' . htmlspecialchars($item['link']) . '" class="adugna-sidebar-link ' . $active . '" data-page="' . htmlspecialchars($item['link']) . '"><span class="adugna-icon"><i class="fas ' . $icon . '"></i></span> <span>' . htmlspecialchars($item['title']) . '</span></a></li>';
                }
            }
        }
        // Use config if available, else fallback to static menu
        if (!empty($sidebarConfig['menu'])) {
            adugna_renderSidebarMenu($sidebarConfig['menu']);
        } else {
            // Fallback static menu (minimal)
            ?>
            <li><a href="dashboard.php" class="adugna-sidebar-link" data-page="dashboard.php"><span class="adugna-icon"><i class="fas fa-tachometer-alt"></i></span> <span>Dashboard</span></a></li>
            <li><a href="active_users.php" class="adugna-sidebar-link" data-page="active_users.php"><span class="adugna-icon"><i class="fas fa-users"></i></span> <span>Active Users</span></a></li>
            <li><a href="add_users.php" class="adugna-sidebar-link" data-page="add_users.php"><span class="adugna-icon"><i class="fas fa-user-plus"></i></span> <span>Add Users</span></a></li>
            <li><a href="roles.php" class="adugna-sidebar-link" data-page="roles.php"><span class="adugna-icon"><i class="fas fa-user-tag"></i></span> <span>Roles</span></a></li>
            <li><a href="settings.php" class="adugna-sidebar-link" data-page="settings.php"><span class="adugna-icon"><i class="fas fa-cogs"></i></span> <span>Settings</span></a></li>
            <li>
                <a href="messages.php" class="adugna-sidebar-link" data-page="messages.php">
                    <span class="adugna-icon"><i class="fas fa-envelope"></i></span>
                    Messages
                    <?php if ($unread > 0): ?>
                        <span style="background:#e74c3c;color:#fff;border-radius:50%;padding:2px 7px;font-size:0.85em;font-weight:600;margin-left:6px;">
                            <?= $unread ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="logout.php" class="adugna-sidebar-link" data-page="logout.php"><span class="adugna-icon"><i class="fas fa-sign-out-alt"></i></span> <span>Logout</span></a></li>
            <?php
        }
        ?>
    </ul>
</div>
<!-- Adugna Gizaw: Sidebar toggle and submenu logic, highlight active, responsive -->
<script>
/**
 * Adugna Gizaw: Sidebar toggle, submenu logic, and active page highlight.
 * - Sidebar stays open after navigation on desktop.
 * - Highlights the active page.
 * - On mobile, sidebar can be toggled.
 */
(function() {
    // Sidebar toggle for mobile
    const sidebar = document.getElementById('adugnaSidebar');
    let toggleBtn = document.getElementById('adugnaSidebarToggle');
    if (!toggleBtn) {
        // Add toggle button if not present
        toggleBtn = document.createElement('button');
        toggleBtn.className = 'adugna-sidebar-toggle-btn';
        toggleBtn.id = 'adugnaSidebarToggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleBtn);
    }
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('adugna-closed');
    });

    // Keep sidebar open on desktop, close on mobile navigation
    function handleSidebarOnResize() {
        if (window.innerWidth > 900) {
            sidebar.classList.remove('adugna-closed');
        }
    }
    window.addEventListener('resize', handleSidebarOnResize);
    handleSidebarOnResize();

    // Submenu logic
    document.querySelectorAll('.adugna-has-submenu').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = link.nextElementSibling;
            const toggleIcon = link.querySelector('.adugna-submenu-toggle');
            if (submenu && submenu.classList.contains('adugna-submenu')) {
                submenu.classList.toggle('adugna-open');
                if (toggleIcon) toggleIcon.classList.toggle('adugna-rotated');
            }
        });
    });

    // Highlight active page
    const currentPage = location.pathname.split('/').pop();
    document.querySelectorAll('.adugna-sidebar-link[data-page]').forEach(function(link) {
        if (link.getAttribute('data-page') === currentPage) {
            link.classList.add('adugna-active');
            // Open parent submenu if inside submenu
            const submenu = link.closest('.adugna-submenu');
            if (submenu) {
                submenu.classList.add('adugna-open');
                const parentToggle = submenu.parentElement.querySelector('.adugna-submenu-toggle');
                if (parentToggle) parentToggle.classList.add('adugna-rotated');
            }
        }
    });

    // Close sidebar on mobile after navigation
    document.querySelectorAll('.adugna-sidebar-link[data-page]').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 900) {
                sidebar.classList.add('adugna-closed');
            }
        });
    });
})();
</script>
