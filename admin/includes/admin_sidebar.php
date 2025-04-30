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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/active_user.css">
<!-- Adugna Gizaw: Use style.css for all sidebar and layout styling -->
<link rel="stylesheet" href="../assets/css/style.css">

<!-- Sidebar Hamburger Toggle Button (always visible, fixed at top left) -->
<style>
/* Adugna Gizaw: Revamped outstanding sidebar styles */
body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e0e7ff 100%) !important;
    font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
    font-size: 15px;
    color: #222;
    letter-spacing: 0.01em;
}
.adugna-sidebar {
    background: linear-gradient(120deg, #2563eb 0%, #38bdf8 100%) !important;
    color: #fff !important;
    border-right: 2px solid #e0e7ff;
    box-shadow: 2px 0 16px rgba(56,189,248,0.13);
    font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
    font-size: 1.08em;
    font-weight: 500;
    letter-spacing: 0.01em;
}
.adugna-sidebar .adugna-logo {
    font-size: 1.45em;
    font-weight: 800;
    color: #fff;
    padding: 1.3em 1.1em 1em 1.1em;
    letter-spacing: 0.05em;
    background: rgba(255,255,255,0.10);
    border-bottom: 1px solid #e0e7ff33;
    margin-bottom: 0.7em;
    display: flex;
    align-items: center;
    gap: 0.7em;
    font-family: 'Montserrat', 'Inter', 'Segoe UI', Arial, sans-serif;
    text-shadow: 0 2px 8px #2563eb44;
}
.adugna-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.adugna-sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.8em;
    padding: 0.85em 1.3em;
    color: #e0e7ff;
    font-size: 1.07em;
    font-weight: 600;
    border-radius: 0.6em;
    margin: 0.13em 0.6em;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s, font-size 0.18s;
    text-decoration: none;
    position: relative;
    font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
    letter-spacing: 0.01em;
    box-shadow: 0 1px 6px rgba(44,62,80,0.07);
}
.adugna-sidebar-link .adugna-icon {
    min-width: 1.3em;
    text-align: center;
    color: #bae6fd;
    font-size: 1.18em;
    transition: margin 0.2s, color 0.18s;
    filter: drop-shadow(0 2px 6px #38bdf855);
}
.adugna-sidebar-link.adugna-active, .adugna-sidebar-link:hover, .adugna-sidebar-link:focus {
    background: linear-gradient(90deg, #38bdf8 0%, #2563eb 100%);
    color: #fff;
    box-shadow: 0 4px 18px 0 rgba(80, 112, 255, 0.13);
    font-size: 1.13em;
}
.adugna-sidebar-link.adugna-active .adugna-icon, .adugna-sidebar-link:hover .adugna-icon {
    color: #fff;
    text-shadow: 0 2px 8px #2563eb55;
}
.adugna-sidebar .adugna-submenu {
    background: rgba(255,255,255,0.10);
    border-radius: 0.5em;
    margin: 0.2em 0.6em 0.2em 1.7em;
    padding: 0.2em 0.2em 0.2em 0.8em;
    display: none;
    font-size: 0.98em;
}
.adugna-sidebar .adugna-submenu.adugna-open {
    display: block;
}
.adugna-submenu-toggle {
    margin-left: auto;
    color: #bae6fd;
    font-size: 1em;
    transition: transform 0.2s, color 0.18s;
}
.adugna-submenu-toggle.adugna-rotated {
    transform: rotate(90deg);
    color: #fff;
}
.adugna-sidebar-toggle-btn {
    position: fixed;
    top: 16px;
    left: 16px;
    z-index: 1100;
    background: #2563eb !important;
    color: #fff !important;
    border: none;
    box-shadow: 0 2px 8px rgba(80,112,255,0.13);
    font-size: 1.2em;
    border-radius: 4px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.adugna-sidebar-toggle-btn:hover {
    background: #38bdf8 !important;
    color: #fff !important;
}
.adugna-sidebar-toggle-btn .adugna-hamburger {
    width: 24px;
    height: 18px;
    display: inline-block;
    position: relative;
}
.adugna-sidebar-toggle-btn .adugna-hamburger span {
    display: block;
    height: 3px;
    width: 100%;
    background: #fff;
    border-radius: 2px;
    margin-bottom: 5px;
    transition: all 0.3s;
}
.adugna-sidebar-toggle-btn .adugna-hamburger span:last-child {
    margin-bottom: 0;
}
.adugna-sidebar-overlay {
    background: rgba(56,189,248,0.13) !important;
}
.adugna-sidebar li {
    margin-bottom: 0.08em;
}
.adugna-sidebar .adugna-sidebar-link .adugna-icon i {
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
}
.adugna-sidebar .adugna-sidebar-link span {
    font-family: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
}
@media (max-width: 1100px) {
    .adugna-sidebar {
        font-size: 1em;
    }
    .adugna-sidebar .adugna-logo {
        font-size: 1.15em;
    }
}
@media (max-width: 900px) {
    .adugna-sidebar {
        font-size: 0.98em;
        width: 220px;
    }
    .adugna-sidebar .adugna-logo {
        font-size: 1em;
        padding: 1em 0.7em 0.8em 0.7em;
    }
}
@media (max-width: 600px) {
    .adugna-sidebar {
        font-size: 0.95em;
        width: 98vw;
        min-width: 0;
        max-width: 100vw;
    }
    .adugna-sidebar .adugna-logo {
        font-size: 0.95em;
        padding: 0.7em 0.5em 0.7em 0.5em;
    }
    .adugna-sidebar-link {
        font-size: 1em;
        padding: 0.7em 0.7em;
    }
    .adugna-sidebar .adugna-submenu {
        font-size: 0.93em;
        margin: 0.1em 0.3em 0.1em 1em;
        padding: 0.1em 0.1em 0.1em 0.5em;
    }
}
</style>

<!-- Hamburger Toggle Button -->
<button class="adugna-sidebar-toggle-btn" id="adugnaSidebarToggle" aria-label="Toggle sidebar">
    <span class="adugna-hamburger">
        <span></span>
        <span></span>
        <span></span>
    </span>
</button>

<div class="adugna-sidebar adugna-collapsed" id="adugnaSidebar">
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
<div class="adugna-sidebar-overlay" id="adugnaSidebarOverlay"></div>
<!-- Adugna Gizaw: Sidebar toggle and submenu logic, highlight active, responsive -->
<script>
/**
 * Adugna Gizaw: Sidebar toggle, submenu logic, and active page highlight.
 * - Sidebar is collapsible via hamburger button.
 * - On desktop, sidebar pushes content; on mobile, overlays content.
 * - Sidebar never covers content on desktop.
 * - Overlay closes sidebar on mobile.
 */
(function() {
    const sidebar = document.getElementById('adugnaSidebar');
    const toggleBtn = document.getElementById('adugnaSidebarToggle');
    const overlay = document.getElementById('adugnaSidebarOverlay');
    // Main content wrapper (add class to your main content container for push effect)
    let mainContent = document.querySelector('.adugna-main-content');

    // Open sidebar
    function openSidebar() {
        sidebar.classList.remove('adugna-collapsed');
        if (window.innerWidth <= 900) {
            document.body.classList.add('adugna-sidebar-open');
            overlay.style.display = 'block';
        } else {
            document.body.classList.remove('adugna-sidebar-open');
            overlay.style.display = 'none';
        }
    }
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.add('adugna-collapsed');
        document.body.classList.remove('adugna-sidebar-open');
        overlay.style.display = 'none';
    }
    // Toggle sidebar
    function toggleSidebar() {
        if (sidebar.classList.contains('adugna-collapsed')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    }
    // Initial state: collapsed on mobile, open on desktop
    function handleSidebarOnResize() {
        if (window.innerWidth > 900) {
            sidebar.classList.remove('adugna-collapsed');
            document.body.classList.remove('adugna-sidebar-open');
            overlay.style.display = 'none';
        } else {
            sidebar.classList.add('adugna-collapsed');
            document.body.classList.remove('adugna-sidebar-open');
            overlay.style.display = 'none';
        }
    }
    window.addEventListener('resize', handleSidebarOnResize);
    handleSidebarOnResize();

    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleSidebar();
    });
    // Overlay click closes sidebar
    overlay.addEventListener('click', closeSidebar);

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

    // On mobile, close sidebar after navigation
    document.querySelectorAll('.adugna-sidebar-link[data-page]').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 900) {
                closeSidebar();
            }
        });
    });

    // Optional: close sidebar if user clicks outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 900 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            closeSidebar();
        }
    });
})();
</script>
