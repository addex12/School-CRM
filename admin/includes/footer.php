<?php 
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
?>
<link rel="stylesheet" href="includes/footer.css">
<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-section footer-main-info">
            <h4><?php echo $pageTitle ?? 'Admin Panel'; ?></h4>
            <p>&copy; <?php echo date('Y'); ?> School CRM System</p>
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
