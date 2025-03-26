</main>
<footer class="main-footer">
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
.main-footer {
    background-color: #f5f5f5;
    padding: 20px;
    border-top: 1px solid #ddd;
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
    margin-bottom: 10px;
    font-size: 1.2em;
}

.footer-section ul {
    list-style-type: none;
    padding: 0;
}

.footer-section ul li {
    margin: 5px 0;
}

.footer-section ul li a {
    text-decoration: none;
    color: #007bff; /* Bootstrap primary color */
}

.footer-section ul li a:hover {
    text-decoration: underline;
}

.footer-bottom {
    text-align: center;
    margin-top: 20px;
    padding: 10px;
    font-size: 0.9em;
    color: #555;
}