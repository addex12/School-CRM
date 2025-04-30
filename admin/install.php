<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna Gizaw: Full ERPNext-inspired, adugna-patented system installation page
require_once 'includes/db.php';

$statusMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_system'])) {
    try {
        // Create necessary tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                role ENUM('admin', 'teacher', 'parent', 'student') NOT NULL DEFAULT 'parent',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL DEFAULT NULL,
                last_activity TIMESTAMP NULL DEFAULT NULL,
                reset_token VARCHAR(255) NULL,
                reset_token_expires DATETIME NULL
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS surveys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category_id INT,
                target_roles JSON,
                created_by INT NOT NULL,
                starts_at DATETIME,
                ends_at DATETIME,
                languages JSON,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES survey_categories(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS survey_fields (
                id INT AUTO_INCREMENT PRIMARY KEY,
                survey_id INT NOT NULL,
                field_type ENUM('text', 'textarea', 'radio', 'checkbox', 'select', 'number', 'date', 'rating', 'file') NOT NULL,
                field_label VARCHAR(255) NOT NULL,
                field_name VARCHAR(255) NOT NULL UNIQUE,
                field_options JSON,
                is_required BOOLEAN DEFAULT FALSE,
                validation_rules JSON,
                display_order INT NOT NULL,
                translations JSON,
                FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS survey_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                survey_id INT NOT NULL,
                user_id INT NOT NULL,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS response_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                response_id INT NOT NULL,
                field_id INT NOT NULL,
                field_value TEXT,
                FOREIGN KEY (response_id) REFERENCES survey_responses(id) ON DELETE CASCADE,
                FOREIGN KEY (field_id) REFERENCES survey_fields(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS survey_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS feedback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                subject VARCHAR(255),
                message TEXT NOT NULL,
                rating INT CHECK (rating BETWEEN 1 AND 5),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                status ENUM('open', 'pending', 'resolved') DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(255) NOT NULL UNIQUE,
                setting_value TEXT,
                setting_group VARCHAR(255) DEFAULT 'general',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                read_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $statusMsg = "Database tables created successfully!";
    } catch (PDOException $e) {
        $errorMsg = "Error creating tables: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Installation - School CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        body { background: #f5f7fa; }
        .adugna-install-main {
            max-width: 520px;
            margin: 48px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(25, 118, 210, 0.09);
            padding: 32px 22px 38px 22px;
            transition: box-shadow 0.2s;
        }
        .adugna-install-header {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 800;
            margin-bottom: 22px;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .adugna-install-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-install-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 7px 18px;
            font-size: 1em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 4px rgba(25,118,210,0.07);
        }
        .adugna-install-btn i { font-size: 1.1em; }
        .adugna-install-btn:hover, .adugna-install-btn:focus { background: #145ea8; }
        .adugna-install-alert-success {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-install-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-install-list {
            margin: 1.5em 0 1.2em 0;
            padding-left: 1.2em;
            color: #215967;
            font-size: 1em;
        }
        .adugna-install-list li {
            margin-bottom: 0.5em;
            font-size: 0.97em;
        }
        @media (max-width: 600px) {
            .adugna-install-main { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-install-header { font-size: 1.05em; }
        }
    </style>
</head>
<body>
    <div class="adugna-install-main">
        <div class="adugna-install-header">
            <i class="fas fa-cogs"></i> School CRM System Installation
        </div>
        <?php if ($statusMsg): ?>
            <div class="adugna-install-alert-success"><?= htmlspecialchars($statusMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="adugna-install-alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <div class="adugna-install-card">
            <h2 style="color:#1976d2;font-size:1.1em;margin-bottom:1em;"><i class="fas fa-info-circle"></i> Installation Steps</h2>
            <ol class="adugna-install-list">
                <li>Click <b>Install System</b> to create all required database tables.</li>
                <li>After installation, create your first admin user from the login page.</li>
                <li>For advanced setup, configure <code>config.php</code> and <code>db.php</code> as needed.</li>
                <li>For security, remove or restrict access to this page after installation.</li>
            </ol>
            <form method="post" style="text-align:center;">
                <button type="submit" name="install_system" class="adugna-install-btn"><i class="fas fa-database"></i> Install System</button>
            </form>
        </div>
        <div style="text-align:center;color:#888;font-size:0.97em;margin-top:2em;">
            &copy; <?= date('Y') ?> Adugna Gizaw | School CRM
        </div>
    </div>
</body>
</html>
