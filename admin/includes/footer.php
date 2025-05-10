<?php 
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
?>
<style>
/**
 * Adugna Gizaw: adugna-footer is always fixed at the bottom, content/screen aware, interactive.
 * - Uses adugna- prefix for all custom styles.
 * - Footer never overlaps content: add bottom padding to body/main if needed.
 * - Responsive and visually outstanding.
 * - Footer is aware of adugna-sidebar state (collapsed/expanded).
 */
.adugna-footer {
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100vw;
    background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
    color: #f5f6fa;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    border-top: 1px solid #374150;
    font-size: 12px;
    z-index: 1000;
    box-shadow: 0 -2px 8px rgba(44,62,80,0.07);
    min-height: 44px;
    transition: background 0.2s, left 0.25s, width 0.25s;
}

/* Sidebar-aware footer: shift right if sidebar is expanded on desktop */
@media (min-width: 901px) {
    body:not(.adugna-sidebar-collapsed) .adugna-footer {
        left: 240px; /* match sidebar width */
        width: calc(100vw - 240px);
    }
    body.adugna-sidebar-collapsed .adugna-footer {
        left: 0;
        width: 100vw;
    }
}

.adugna-footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    gap: 16px;
    flex-wrap: wrap;
}
.adugna-footer-section {
    flex: 1 1 0;
    min-width: 0;
    padding: 0 6px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.adugna-footer-main-info {
    max-width: 180px;
    font-weight: 500;
}
.adugna-footer-main-info h4 {
    margin: 0 0 2px 0;
    font-size: 13px;
    color: #f1c40f;
    letter-spacing: 0.5px;
}
.adugna-footer-main-info p {
    margin: 0;
    font-size: 10px;
    color: #bfc9d1;
}
.adugna-quick-link-list {
    display: flex;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
    flex-wrap: wrap;
}
.adugna-footer-link {
    color: #f5f6fa;
    text-decoration: none;
    font-size: 10px;
    font-weight: 500;
    transition: color 0.2s, background 0.2s, box-shadow 0.2s;
    padding: 4px 8px;
    border-radius: 4px;
    background: #2563eb;
    margin-right: 2px;
    border: 1px solid #215967;
    box-shadow: 0 1px 2px rgba(44,62,80,0.04);
    display: inline-block;
}
.adugna-footer-link:hover, .adugna-footer-link:focus {
    color: #fff;
    background: #215967;
    text-decoration: none;
    box-shadow: 0 2px 8px #1976d2;
}
.adugna-developer-info {
    text-align: right;
    flex-shrink: 0;
    min-width: 120px;
}
.adugna-social-links {
    display: flex;
    gap: 7px;
    margin-top: 2px;
}
.adugna-social-link {
    color: #f5f6fa;
    font-size: 13px;
    transition: color 0.2s, transform 0.2s, background 0.2s;
    background: #007bfc;
    border-radius: 50%;
    padding: 3px 5px;
    display: inline-block;
}
.adugna-social-link:hover, .adugna-social-link:focus {
    color: #f1c40f;
    background: #215967;
    transform: scale(1.08);
}
@media (max-width: 900px) {
    .adugna-footer {
        left: 0 !important;
        width: 100vw !important;
    }
    .adugna-footer-content {
        flex-direction: column;
        gap: 6px;
        align-items: flex-start;
    }
    .adugna-developer-info {
        text-align: left;
    }
}
@media (max-width: 600px) {
    .adugna-footer {
        padding: 8px 4px;
        font-size: 10px;
    }
    .adugna-footer-main-info {
        max-width: 100%;
    }
    .adugna-footer-section {
        padding: 0 2px;
    }
    .adugna-quick-link-list {
        gap: 6px;
    }
    .adugna-social-links {
        gap: 4px;
    }
    .adugna-footer-link {
        padding: 3px 6px;
        font-size: 9px;
    }
}
/* Ensure page content is never hidden behind the fixed footer */
body, .admin-dashboard, .adugna-main, .adugna-main-content {
    padding-bottom: 60px !important;
    box-sizing: border-box;
}
</style>

<footer class="adugna-footer">
    <!-- Adugna Gizaw: Responsive, fixed, interactive footer. Never overlaps content. Sidebar-aware. -->
    <div class="adugna-footer-content">
        <div class="adugna-footer-section adugna-footer-main-info">
            <h4><?php echo $pageTitle ?? 'Admin Panel'; ?></h4>
            <p>&copy; <?php echo date('Y'); ?> School CRM System</p>
        </div>
        <div class="adugna-footer-section">
            <?php
            // Sanitize current file name for safe comparison and output
            $currentPage = htmlspecialchars(basename($_SERVER['PHP_SELF']));
            ?>
            <ul class="adugna-quick-link-list">
                <li><a href="dashboard.php" class="adugna-footer-link<?= $currentPage === 'dashboard.php' ? ' active' : '' ?>"><i class="fas fa-home" style="font-size:11px;margin-right:2px;"></i>Dashboard</a></li>
                <li><a href="users.php" class="adugna-footer-link<?= $currentPage === 'users.php' ? ' active' : '' ?>"><i class="fas fa-users" style="font-size:11px;margin-right:2px;"></i>Users</a></li>
                <li><a href="../../logout.php" class="adugna-footer-link"><i class="fas fa-sign-out-alt" style="font-size:11px;margin-right:2px;"></i>Logout</a></li>
            </ul>
        </div>
        <div class="adugna-footer-section adugna-developer-info" style="text-align:left; min-width:unset; max-width:140px; word-break:break-word;">
            <div class="adugna-social-links">
                <a href="https://www.linkedin.com/in/eleganceict" target="_blank" class="adugna-social-link" aria-label="LinkedIn">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="https://twitter.com/eleganceict1" target="_blank" class="adugna-social-link" aria-label="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://github.com/addex12" target="_blank" class="adugna-social-link" aria-label="GitHub">
                    <i class="fab fa-github"></i>
                </a>
            </div>
            <p style="margin: 5px 0 0 0; font-size:9px; color:#bfc9d1; word-break:break-word;">
                Developed by Adugna Gizaw
            </p>
        </div>
    </div>
</footer>
<script>
/**
 * Adugna Gizaw: Make footer aware of adugna-sidebar state (collapsed/expanded).
 * Adds/removes .adugna-sidebar-collapsed on body based on sidebar state.
 */
(function() {
    // Detect sidebar and toggle class on body for footer awareness
    function updateFooterSidebarState() {
        var sidebar = document.getElementById('adugnaSidebar');
        if (!sidebar) return;
        if (sidebar.classList.contains('adugna-collapsed')) {
            document.body.classList.add('adugna-sidebar-collapsed');
        } else {
            document.body.classList.remove('adugna-sidebar-collapsed');
        }
    }
    // Listen for sidebar toggle button
    var sidebarToggle = document.getElementById('adugnaSidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            setTimeout(updateFooterSidebarState, 260); // match sidebar transition
        });
    }
    // Also update on window resize (sidebar may auto-collapse)
    window.addEventListener('resize', function() {
        setTimeout(updateFooterSidebarState, 100);
    });
    // Initial check
    document.addEventListener('DOMContentLoaded', updateFooterSidebarState);
})();
</script>
