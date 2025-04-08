<style>
    .admin-footer {
        position: sticky;
        bottom: 0;
        left: 250px;
        width: calc(100% - 250px);
        background-color: #2c3e50;
        color: white;
        padding: 8px 15px;
        display: flex;
        align-items: center;
        border-top: 1px solid #374150;
        font-size: 12px;
        z-index: 100;
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 15px;
    }

    .footer-section {
        flex: 1;
        min-width: 0;
        padding: 0 5px;
    }

    .footer-main-info {
        max-width: 200px;
    }

    .footer-link {
        color: white;
        text-decoration: none;
        font-size: 11px;
    }

    .footer-link:hover {
        text-decoration: underline;
    }

    .social-links {
        display: flex;
        gap: 8px;
        margin-top: 3px;
    }

    .social-link {
        color: white;
        font-size: 14px;
        transition: opacity 0.2s;
    }

    .social-link:hover {
        opacity: 0.8;
    }

    .quick-link-list {
        display: flex;
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .developer-info {
        text-align: right;
        flex-shrink: 0;
    }

    .footer-section h4 {
        margin: 0 0 2px 0;
        font-size: 12px;
    }

    .footer-section p {
        margin: 0;
        line-height: 1.3;
    }
</style>

<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-section footer-main-info">
            <h4><?php echo $pageTitle; ?></h4>
            <p>&copy; <?php echo date('Y'); ?> School Survey System</p>
        </div>
        
        <div class="footer-section">
            <ul class="quick-link-list">
                <li><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                <li><a href="surveys.php" class="footer-link">Surveys</a></li>
                <li><a href="users.php" class="footer-link">Users</a></li>
                <li><a href="results.php" class="footer-link">Results</a></li>
                <li><a href="../../logout.php" class="footer-link">Logout</a></li>
            </ul>
        </div>

        <div class="footer-section developer-info">
            <div class="social-links">
                <a href="https://www.linkedin.com/in/eleganceict" target="_blank" class="social-link">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="https://twitter.com/eleganceict1" target="_blank" class="social-link">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://github.com/addex12" target="_blank" class="social-link">
                    <i class="fab fa-github"></i>
                </a>
            </div>
            <p class="footer-link">
                <a href="mailto:gizawadugna@gmail.com">Adugna Gizaw</a>
            </p>
        </div>
    </div>
</footer>
