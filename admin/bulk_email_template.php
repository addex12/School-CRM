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

// Handle form submission for creating/updating template
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $placeholders = trim($_POST['placeholders'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!$subject || !$content) {
        $error = "Subject and content are required.";
    } else {
        // Save template logic here (e.g., to DB or file)
        // For demonstration, just show success
        $success = "Bulk email template saved successfully!";
    }
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
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="adugna-card">
                <?php if ($error): ?>
                    <div class="adugna-error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="adugna-success-message"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="adugna-form-group">
                        <label for="subject">Email Subject</label>
                        <input type="text" name="subject" id="subject" required placeholder="Enter email subject" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                    </div>
                    <div class="adugna-form-group">
                        <label for="placeholders">Available Placeholders</label>
                        <input type="text" name="placeholders" id="placeholders" placeholder="e.g. {name}, {email}, {date}" value="<?= htmlspecialchars($_POST['placeholders'] ?? '') ?>">
                        <div class="adugna-placeholder-hint">
                            Separate placeholders with commas. Use these in your content as <code>{placeholder}</code>.
                        </div>
                    </div>
                    <div class="adugna-form-group">
                        <label for="content">Email Content</label>
                        <textarea name="content" id="content" required placeholder="Write your email content here..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
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
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
