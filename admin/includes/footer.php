<style>
    .admin-footer {
        position: sticky;
        bottom: 0;
        left: 0;
        width: 100%;
        background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
        color: #f5f6fa;
        padding: 14px 30px;
        display: flex;
        align-items: center;
        border-top: 1px solid #374150;
        font-size: 13px;
        z-index: 100;
        box-shadow: 0 -2px 8px rgba(44,62,80,0.07);
    }
    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 30px;
        flex-wrap: wrap;
    }
    .footer-section {
        flex: 1 1 0;
        min-width: 0;
        padding: 0 10px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .footer-main-info {
        max-width: 220px;
        font-weight: 500;
    }
    .footer-main-info h4 {
        margin: 0 0 3px 0;
        font-size: 15px;
        color: #f1c40f;
        letter-spacing: 0.5px;
    }
    .footer-main-info p {
        margin: 0;
        font-size: 12px;
        color: #bfc9d1;
    }
    .quick-link-list {
        display: flex;
        gap: 18px;
        margin: 0;
        padding: 0;
        list-style: none;
        flex-wrap: wrap;
    }
    .footer-link {
        color: #f5f6fa;
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        transition: color 0.2s;
    }
    .footer-link:hover {
        color: #f1c40f;
        text-decoration: underline;
    }
    .developer-info {
        text-align: right;
        flex-shrink: 0;
        min-width: 160px;
    }
    .social-links {
        display: flex;
        gap: 12px;
        margin-top: 2px;
    }
    .social-link {
        color: #f5f6fa;
        font-size: 17px;
        transition: color 0.2s, transform 0.2s;
    }
    .social-link:hover {
        color: #f1c40f;
        transform: scale(1.15);
    }
    @media (max-width: 900px) {
        .footer-content {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        .developer-info {
            text-align: left;
        }
    }
    @media (max-width: 600px) {
        .admin-footer {
            padding: 10px 8px;
            font-size: 12px;
        }
        .footer-main-info {
            max-width: 100%;
        }
        .footer-section {
            padding: 0 2px;
        }
        .quick-link-list {
            gap: 10px;
        }
        .social-links {
            gap: 8px;
        }
    }
</style>

<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-section footer-main-info">
            <h4><?php echo $pageTitle ?? 'Admin Panel'; ?></h4>
            <p>&copy; <?php echo date('Y'); ?> School Survey System</p>
        </div>
        <div class="footer-section">
            <ul class="quick-link-list">
                <li><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                <li><a href="surveys.php" class="footer-link">Surveys</a></li>
                <li><a href="users.php" class="footer-link">Users</a></li>
                <li><a href="../../logout.php" class="footer-link">Logout</a></li>
            </ul>
        </div>
        <div class="footer-section developer-info">
            <div class="social-links">
                <a href="https://www.linkedin.com/in/eleganceict" target="_blank" class="social-link" aria-label="LinkedIn">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="https://twitter.com/eleganceict1" target="_blank" class="social-link" aria-label="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://github.com/addex12" target="_blank" class="social-link" aria-label="GitHub">
                    <i class="fab fa-github"></i>
                </a>
            </div>
            <p style="margin: 6px 0 0 0; font-size:11px; color:#bfc9d1;">
                Developed by Adugna Gizaw
            </p>
        </div>
    </div>
</footer>
