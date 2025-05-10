<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
?>
</main>
<footer class="main-footer">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-footer {
            background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
            color: #f5f6fa;
            padding: 8px 4px 4px 4px;
            border-top: 1.5px solid #007bfc;
            font-size: clamp(9px, 2vw, 12px);
            box-shadow: 0 -1px 4px rgba(44,62,80,0.07);
            margin-top: auto;
            width: 100%;
            flex-shrink: 0;
        }
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2px;
            flex-wrap: wrap;
            width: 100%;
        }
        .footer-section {
            flex: 1 1 0;
            min-width: 60px;
            padding: 0 1px;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .footer-section h4 {
            margin-bottom: 1px;
            font-size: 0.95em;
            color: #f1c40f;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .footer-section ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .footer-section ul li {
            margin: 1px 0;
        }
        .footer-section ul li a,
        .footer-section a {
            text-decoration: none;
            color: #f5f6fa;
            font-size: 0.95em;
            font-weight: 500;
            padding: 1px 3px;
            border-radius: 3px;
            background: #2563eb;
            margin-right: 1px;
            border: 1px solid #215967;
            box-shadow: 0 1px 2px rgba(44,62,80,0.03);
            display: inline-block;
            transition: background 0.18s, color 0.18s;
            line-height: 1.2;
        }
        .footer-section ul li a:hover,
        .footer-section a:hover {
            background: #215967;
            color: #fff;
            text-decoration: none;
        }
        .footer-section p,
        .footer-section a {
            color: #bfc9d1;
            font-size: 0.93em;
            margin: 0;
        }
        .footer-section .footer-link {
            color: #f5f6fa;
        }
        .footer-section .footer-link:hover {
            color: #f1c40f;
        }
        .footer-bottom {
            text-align: center;
            margin-top: 2px;
            padding: 1px 0 0 0;
            font-size: 0.9em;
            color: #bfc9d1;
        }
        @media (max-width: 900px) {
            .footer-content {
                flex-direction: column;
                gap: 1px;
                align-items: flex-start;
            }
            .footer-section {
                min-width: 100%;
                padding: 0 1px;
            }
        }
        @media (max-width: 600px) {
            .main-footer {
                padding: 2px 0 1px 0;
                font-size: clamp(7px, 3vw, 9px);
            }
            .footer-content {
                flex-direction: column;
                gap: 0;
                align-items: stretch;
            }
            .footer-section {
                padding: 0 1px;
                min-width: 0;
            }
            .footer-section h4 {
                font-size: 0.9em;
            }
            .footer-section ul li a,
            .footer-section a {
                padding: 1px 2px;
                font-size: 0.9em;
            }
        }
        @media (max-width: 400px) {
            .main-footer {
                font-size: clamp(6px, 4vw, 8px);
                padding: 1px 0 0 0;
            }
            .footer-section h4 {
                font-size: 0.85em;
            }
        }
    </style>
    <div class="footer-content">
        <div class="footer-section quick-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                <li><a href="faq.php" class="footer-link" onclick="if(!window.faqExists){alert('FAQ page coming soon!');return false;}">FAQ</a></li>
                <li><a href="terms.php" class="footer-link" onclick="if(!window.termsExists){alert('Terms of Service page coming soon!');return false;}">Terms of Service</a></li>
            </ul>
        </div>
        <div class="footer-section contact-info">
            <h4>Contact Info</h4>
            <p>Email: <a href="mailto:contactus@flipperschools.com" class="footer-link">contactus@flipperschools.com</a></p>
        </div>
        <div class="footer-section developer-info">
            <h4>Developer</h4>
                <a href="https://www.linkedin.com/in/eleganceict" target="_blank" class="footer-link">LinkedIn</a>
                <a href="https://github.com/addex12" target="_blank" class="footer-link">GitHub</a>
            </p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> School CRM System. All rights reserved.</p>
    </div>
</footer>
<script src="../assets/js/main.js"></script>
<script src="../includes/activity-tracker.js"></script>
</body>
</html>