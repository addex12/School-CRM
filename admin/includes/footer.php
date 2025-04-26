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
    .admin-footer {
        position: relative;
        bottom: 0;
        left: 0;
        width: 100%;
        background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
        color: #f5f6fa;
        padding: 18px 30px; /* Removed fixed height, using padding for spacing */
        display: flex;
        align-items: center;
        border-top: 1px solid #374150;
        font-size: 13px;
        z-index: 100;
        box-shadow: 0 -2px 8px rgba(44,62,80,0.07);
        flex-shrink: 0; /* Ensures the footer does not shrink */
    }
    .admin-main {
        display: flex;
        flex-direction: column;
        min-height: 100vh; /* Ensures the page takes up the full height of the viewport */
    }
    .content {
        flex: 1; /* Ensures the content takes up available space */
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
        align-items: center; /* Center content for better responsiveness */
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
        color: #fff;
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
        padding: 6px 14px;
        border-radius: 4px;
        background: #007bfc; /* Updated to match Frappe/Jinja design */
        margin-right: 4px;
        border: none; /* Removed border */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
        display: inline-block;
    }
    .footer-link:hover {
        background: #0056b3; /* Darker shade for hover effect */
        transform: translateY(-2px); /* Slight lift on hover */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); /* Enhanced shadow on hover */
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
        background: #007bfc;
        border-radius: 50%;
        padding: 4px 7px 2px 7px;
        display: inline-block;
    }
    .social-link:hover {
        color: #f1c40f;
        background: #215967;
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
        .footer-link {
            padding: 5px 10px;
            font-size: 11px;
        }
        .footer-section {
            padding: 0 5px; /* Adjust padding for smaller screens */
        }
        .footer-main-info {
            text-align: center; /* Center align text for smaller screens */
        }
        .quick-link-list {
            justify-content: center; /* Center links for smaller screens */
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
