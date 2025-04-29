<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Bulk Email Template";

// Ensure templates table exists (migration SQL provided below)
$error = '';
$success = '';
try {
    $pdo->query("SELECT 1 FROM templates LIMIT 1");
} catch (PDOException $e) {
    $error = "Templates table not found. Please run the following SQL migration:<br>
    <pre style='background:#f5f7fa;padding:8px;border-radius:4px;overflow-x:auto;font-size:0.93em;'>
CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    placeholders VARCHAR(255),
    content TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
    </pre>";
}

// Handle form submission for creating/updating template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $subject = trim($_POST['subject'] ?? '');
    $placeholders = trim($_POST['placeholders'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!$subject || !$content) {
        $error = "Subject and content are required.";
    } else {
        // Save template to DB (insert or update by name)
        $name = 'bulk_email_template';
        $stmt = $pdo->prepare("SELECT id FROM templates WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $update = $pdo->prepare("UPDATE templates SET subject=?, placeholders=?, content=?, updated_at=NOW() WHERE name=?");
            $ok = $update->execute([$subject, $placeholders, $content, $name]);
        } else {
            $insert = $pdo->prepare("INSERT INTO templates (name, subject, placeholders, content) VALUES (?, ?, ?, ?)");
            $ok = $insert->execute([$name, $subject, $placeholders, $content]);
        }
        if ($ok) {
            $success = "Bulk email template saved successfully!";
        } else {
            $error = "Failed to save template to database.";
        }
    }
}

// Load existing template for editing
$template = ['subject'=>'','placeholders'=>'','content'=>''];
if (empty($error)) {
    $stmt = $pdo->prepare("SELECT subject, placeholders, content FROM templates WHERE name = ?");
    $stmt->execute(['bulk_email_template']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $template = $row;
    // If just posted, prefer POST values
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $template['subject'] = $_POST['subject'] ?? '';
        $template['placeholders'] = $_POST['placeholders'] ?? '';
        $template['content'] = $_POST['content'] ?? '';
    }
} else {
    // If error (table missing), use POST values if any
    $template['subject'] = $_POST['subject'] ?? '';
    $template['placeholders'] = $_POST['placeholders'] ?? '';
    $template['content'] = $_POST['content'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin: 2rem auto;
            max-width: 540px;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card h2 {
            font-size: 1.13em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 1em;
            letter-spacing: 0.01em;
        }
        .adugna-form-group {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.2em;
        }
        .adugna-form-group label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input,
        .adugna-form-group textarea {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 14px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 1em;
        }
        .adugna-btn:hover {
            background: #145ea8;
        }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover {
            background: #d0e2fa;
        }
        .adugna-error-message {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        .adugna-success-message {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        @media (max-width: 600px) {
            .adugna-card { padding: 0.7rem; }
        }
        .adugna-placeholder-hint {
            font-size: 0.93em;
            color: #888;
            margin-bottom: 0.5em;
        }
        .adugna-content-wrapper {
            max-width: 600px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        @media (max-width: 900px) {
            .adugna-content-wrapper { max-width: 99vw; margin: 18px 2vw 0 2vw; padding: 10px 4px 18px 4px; }
        }
        @media (max-width: 600px) {
            .adugna-content-wrapper { padding: 0.5rem 0.2rem 0.7rem 0.2rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="adugna-content-wrapper">
                <header class="admin-header" style="margin-bottom:1.2em;">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                </header>
                <div class="adugna-card">
                    <?php if ($error): ?>
                        <div class="adugna-error-message"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="adugna-success-message"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="adugna-form-group">
                            <label for="subject">Email Subject</label>
                            <input type="text" name="subject" id="subject" required placeholder="Enter email subject" value="<?= htmlspecialchars($template['subject']) ?>">
                        </div>
                        <div class="adugna-form-group">
                            <label for="placeholders">Available Placeholders</label>
                            <input type="text" name="placeholders" id="placeholders" placeholder="e.g. {name}, {email}, {date}" value="<?= htmlspecialchars($template['placeholders']) ?>">
                            <div class="adugna-placeholder-hint">
                                Separate placeholders with commas. Use these in your content as <code>{placeholder}</code>.
                            </div>
                        </div>
                        <div class="adugna-form-group">
                            <label for="content">Email Content</label>
                            <textarea name="content" id="content" required placeholder="Write your email content here..."><?= htmlspecialchars($template['content']) ?></textarea>
                            <div class="adugna-placeholder-hint">
                                Example: Hello <code>{name}</code>, your email is <code>{email}</code>.
                            </div>
                        </div>
                        <button type="submit" class="adugna-btn">
                            <i class="fas fa-save"></i> Save Template
                        </button>
                        <a href="dashboard.php" class="adugna-btn adugna-btn-secondary" style="margin-left:10px;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
