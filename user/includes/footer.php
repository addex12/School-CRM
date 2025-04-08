</main>
<footer class="main-footer">
    <style>
        .main-footer {
            background-color: #343a40; /* Dark background */
            color: #ffffff; /* White text */
            padding: 40px 20px;
            border-top: 5px solid #007bff; /* Blue top border */
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .footer-section {
            flex: 1;
            min-width: 200px; /* Ensures sections don't get too small */
            margin: 10px;
        }

        .footer-section h4 {
            margin-bottom: 15px;
            font-size: 1.5em;
            color: #007bff; /* Blue headings */
        }

        .footer-section ul {
            list-style-type: none;
            padding: 0;
        }

        .footer-section ul li {
            margin: 10px 0;
        }

        .footer-section ul li a {
            text-decoration: none;
            color: #ffffff; /* White links */
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: #007bff; /* Change link color on hover */
            text-decoration: underline;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            font-size: 0.9em;
            color: #cccccc; /* Light gray text */
        }
    </style>
    <div class="footer-content">
        <div class="footer-section quick-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="terms.php">Terms of Service</a></li>
            </ul>
        </div>
        <div class="footer-section contact-info">
            <h4>Contact Info</h4>
            <p>Email: <a href="mailto:contactus@flipperschools.com">contactus@flipperschools.com</a></p>
            <p>Phone: <a href="tel:+251925582067">+251925582067</a></p>
        </div>
        <div class="footer-section developer-info">
            <h4>Developer</h4>
            <p><strong>Adugna Gizaw</strong></p>
            <p>Email: <a href="mailto:gizawadugna@gmail.com">gizawadugna@gmail.com</a></p>
            <p>
                <a href="https://www.linkedin.com/in/eleganceict" target="_blank">LinkedIn</a> |
                <a href="https://twitter.com/eleganceict1" target="_blank">Twitter</a> |
                <a href="https://github.com/addex12" target="_blank">GitHub</a>
            </p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Survey System. All rights reserved.</p>
    </div>
</footer>
<script src="../assets/js/main.js"></script>
</body>
</html>