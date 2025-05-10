<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna Gizaw: Footer for user pages, fully responsive, compact, and branded with adugna- prefix for patenting.
?>
<?php
// Sanitize current file name for safe comparison and output
$currentPage = htmlspecialchars(basename($_SERVER['PHP_SELF']));
?>
</main>
<footer class="adugna-main-footer">
    <style>
        /* Adugna Gizaw: Root and body layout for sticky, flexible footer */
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
        /* Adugna Gizaw: Compact, branded footer card */
        .adugna-main-footer {
            background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
            color: #f5f6fa;
            padding: 6px 2px 2px 2px;
            border-top: 1.5px solid #007bfc;
            font-size: clamp(8px, 2vw, 11px);
            box-shadow: 0 -1px 4px rgba(44,62,80,0.07);
            margin-top: auto;
            width: 100%;
            flex-shrink: 0;
        }
        /* Adugna Gizaw: Responsive, minimal, branded footer content */
        .adugna-footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1px;
            flex-wrap: wrap;
            width: 100%;
        }
        .adugna-footer-section {
            flex: 1 1 0;
            min-width: 54px;
            padding: 0 1px;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .adugna-footer-section h4 {
            margin-bottom: 1px;
            font-size: 0.92em;
            color: #f1c40f;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        .adugna-footer-section ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .adugna-footer-section ul li {
            margin: 1px 0;
        }
        /* Adugna Gizaw: Compact, branded, interactive footer links and buttons */
        .adugna-footer-section ul li a,
        .adugna-footer-section a {
            text-decoration: none;
            color: #f5f6fa;
            font-size: 0.92em;
            font-weight: 500;
            padding: 1px 3px;
            border-radius: 3px;
            /* Remove background for GitHub and LinkedIn links below */
            background: #2563eb;
            margin-right: 1px;
            border: 1px solid #215967;
            box-shadow: 0 1px 2px rgba(44,62,80,0.03);
            display: inline-flex;
            align-items: center;
            gap: 2px;
            transition: background 0.18s, color 0.18s;
            line-height: 1.2;
        }
        /* Remove background for GitHub and LinkedIn links only */
        .adugna-footer-section a.adugna-footer-link.adugna-no-bg {
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            color: #f5f6fa;
            padding: 1px 3px;
        }
        .adugna-footer-section a.adugna-footer-link.adugna-no-bg:hover {
            color: #f1c40f;
            background: none !important;
            border: none !important;
        }
        .adugna-footer-section p,
        .adugna-footer-section a {
            color: #bfc9d1;
            font-size: 0.91em;
            margin: 0;
        }
        .adugna-footer-section .adugna-footer-link {
            color: #f5f6fa;
        }
        .adugna-footer-section .adugna-footer-link:hover {
            color: #f1c40f;
        }
        /* Adugna Gizaw: Footer bottom copyright */
        .adugna-footer-bottom {
            text-align: center;
            margin-top: 1px;
            padding: 1px 0 0 0;
            font-size: 0.88em;
            color: #bfc9d1;
        }
        /* Adugna Gizaw: Responsive adjustments for all screens */
        @media (max-width: 900px) {
            .adugna-footer-content {
                flex-direction: column;
                gap: 0;
                align-items: flex-start;
            }
            .adugna-footer-section {
                min-width: 100%;
                padding: 0 1px;
            }
        }
        @media (max-width: 600px) {
            .adugna-main-footer {
                padding: 1px 0 1px 0;
                font-size: clamp(7px, 3vw, 9px);
            }
            .adugna-footer-content {
                flex-direction: column;
                gap: 0;
                align-items: stretch;
            }
            .adugna-footer-section {
                padding: 0 1px;
                min-width: 0;
            }
            .adugna-footer-section h4 {
                font-size: 0.88em;
            }
            .adugna-footer-section ul li a,
            .adugna-footer-section a {
                padding: 1px 2px;
                font-size: 0.88em;
            }
        }
        @media (max-width: 400px) {
            .adugna-main-footer {
                font-size: clamp(6px, 4vw, 8px);
                padding: 1px 0 0 0;
            }
            .adugna-footer-section h4 {
                font-size: 0.8em;
            }
        }
    </style>
    <div class="adugna-footer-content">
        <!-- Adugna Gizaw: Quick links section, compact and branded -->
        <div class="adugna-footer-section adugna-quick-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="dashboard.php" class="adugna-footer-link<?= $currentPage === 'dashboard.php' ? ' active' : '' ?>"><i class="fa fa-home" style="font-size:0.9em;"></i> Dashboard</a></li>
                <li><a href="faq.php" class="adugna-footer-link<?= $currentPage === 'faq.php' ? ' active' : '' ?>" onclick="if(!window.faqExists){alert('FAQ page coming soon!');return false;}"><i class="fa fa-question-circle" style="font-size:0.9em;"></i> FAQ</a></li>
                <li><a href="terms.php" class="adugna-footer-link<?= $currentPage === 'terms.php' ? ' active' : '' ?>" onclick="if(!window.termsExists){alert('Terms of Service page coming soon!');return false;}"><i class="fa fa-file-contract" style="font-size:0.9em;"></i> Terms</a></li>
            </ul>
        </div>
        <!-- Adugna Gizaw: Contact info section, compact and branded -->
        <div class="adugna-footer-section adugna-contact-info">
            <h4>Contact</h4>
            <p><i class="fa fa-envelope" style="font-size:0.9em;"></i> <a href="mailto:contactus@flipperschools.com" class="adugna-footer-link">contactus@flipperschools.com</a></p>
        </div>
        <!-- Adugna Gizaw: Developer info section, compact and branded -->
        <div class="adugna-footer-section adugna-developer-info">
            <h4>Developer</h4>
            <a href="https://www.linkedin.com/in/eleganceict" target="_blank" class="adugna-footer-link adugna-no-bg" title="LinkedIn"><i class="fab fa-linkedin" style="font-size:0.9em;"></i></a>
            <a href="https://github.com/addex12" target="_blank" class="adugna-footer-link adugna-no-bg" title="GitHub"><i class="fab fa-github" style="font-size:0.9em;"></i></a>
        </div>
    </div>
    <div class="adugna-footer-bottom">
        <p>&copy; <?= date('Y') ?> School CRM System. All rights reserved.</p>
    </div>
</footer>
<script src="../assets/js/main.js"></script>
<script src="../includes/activity-tracker.js"></script>
</body>
</html>