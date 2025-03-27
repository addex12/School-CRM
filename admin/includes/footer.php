<style>
    .admin-footer {
        position: fixed;
        bottom: 0;
        left: 250px; /* Matches the sidebar width */
        width: calc(100% - 250px); /* Adjust width to exclude sidebar */
        background-color: #2c3e50;
        color: white;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #374150;
    }

    .footer-section {
        margin: 0 10px;
    }

    .footer-link {
        color: white;
        text-decoration: none;
    }

    .footer-link:hover {
        text-decoration: underline;
    }

    .social-link {
        color: white;
        margin-right: 10px;
        font-size: 18px;
    }

    .social-link:hover {
        color: #0d1216;
    }
</style>

<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4><?php echo $pageTitle; ?> - Admin Panel</h4>
            <p>&copy; <?php echo date('Y'); ?> School Survey System. All rights reserved.</p>
        </div>
        <div class="footer-section developer-info">
            <h4>Developer</h4>
            <p><strong>Adugna Gizaw</strong></p>
            <p>Email: <a href="mailto:gizawadugna@gmail.com" class="footer-link">gizawadugna@gmail.com</a></p>
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
        </div>
        <div class="footer-section system-info">
            <h4>System Information</h4>
            <p><strong>Version:</strong> 1.0.0</p>
            <p><strong>Framework:</strong> Custom PHP Framework</p>
            <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
        </div>
        <div class="footer-section quick-links">
            <h4>Quick Links</h4>
            <ul class="quick-link-list">
                <li><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                <li><a href="surveys.php" class="footer-link">Surveys</a></li>
                <li><a href="users.php" class="footer-link">Users</a></li>
                <li><a href="results.php" class="footer-link">Results</a></li>
                <li><a href="../../logout.php" class="footer-link logout">Logout</a></li>
            </ul>
        </div>
    </div>
</footer>
