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
/* Outstanding, elegant, and responsive sidebar styles */
:root {
    --adugna-primary: #4f8cff;
    --adugna-secondary: #f5f8ff;
    --adugna-accent: #ffb347;
    --adugna-bg: #fff;
    --adugna-dark: #232946;
    --adugna-light: #eaf0fa;
    --adugna-active: #e0eaff;
    --adugna-shadow: 0 4px 24px rgba(79,140,255,0.08);
    --adugna-radius: 18px;
    --adugna-font: 'Inter', 'Segoe UI', 'Roboto', Arial, sans-serif;
}
body, html {
    font-family: var(--adugna-font);
    background: var(--adugna-secondary);
    color: var(--adugna-dark);
    font-size: 17px;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
.adugna-sidebar-toggle-btn {
    position: fixed;
    top: 18px;
    left: 18px;
    z-index: 1201;
    background: var(--adugna-primary);
    border: none;
    outline: none;
    cursor: pointer;
    padding: 10px 12px;
    border-radius: var(--adugna-radius);
    box-shadow: var(--adugna-shadow);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, box-shadow 0.2s;
}
.adugna-sidebar-toggle-btn:hover {
    background: var(--adugna-accent);
    box-shadow: 0 6px 24px rgba(255,179,71,0.12);
}
.adugna-sidebar-toggle-btn .adugna-hamburger span {
    background: var(--adugna-dark);
    height: 4px;
    border-radius: 3px;
    margin-bottom: 6px;
}
.adugna-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 270px;
    background: linear-gradient(135deg, var(--adugna-bg) 80%, var(--adugna-light) 100%);
    box-shadow: var(--adugna-shadow);
    z-index: 1200;
    transition: transform 0.25s cubic-bezier(.4,0,.2,1), width 0.2s;
    will-change: transform;
    border-top-right-radius: var(--adugna-radius);
    border-bottom-right-radius: var(--adugna-radius);
    display: flex;
    flex-direction: column;
    padding: 0 0 24px 0;
}
.adugna-sidebar.adugna-collapsed {
    transform: translateX(-100%);
}
@media (max-width: 900px) {
    .adugna-sidebar {
        width: 90vw;
        min-width: 0;
        max-width: 340px;
        border-radius: 0 18px 18px 0;
        padding-bottom: 32px;
    }
    .adugna-sidebar.adugna-collapsed {
        transform: translateX(-110%);
    }
    .adugna-logo {
        font-size: 1.6rem;
        padding: 24px 0 12px 18px;
    }
    .adugna-sidebar-link {
        padding: 12px 18px 12px 24px;
        font-size: 1rem;
    }
    .adugna-sidebar-link.adugna-active::before {
        left: 8px;
        height: 24px;
    }
    body.adugna-sidebar-open {
        overflow: hidden;
    }
}
@media (max-width: 600px) {
    .adugna-sidebar {
        width: 100vw;
        min-width: 0;
        max-width: 100vw;
        border-radius: 0 0 18px 0;
        padding-bottom: 18px;
    }
    .adugna-logo {
        font-size: 1.2rem;
        padding: 16px 0 8px 12px;
    }
    .adugna-sidebar-link {
        padding: 10px 12px 10px 16px;
        font-size: 0.98rem;
    }
}
/* Outstanding scrollbar */
.adugna-sidebar ul {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--adugna-primary) var(--adugna-light);
}
.adugna-sidebar ul::-webkit-scrollbar {
    width: 7px;
    background: var(--adugna-light);
    border-radius: 8px;
}
.adugna-sidebar ul::-webkit-scrollbar-thumb {
    background: var(--adugna-primary);
    border-radius: 8px;
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
