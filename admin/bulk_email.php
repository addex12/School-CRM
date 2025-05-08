<?php
// Start session at the very top for session reliability
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// TEMP: Debug session values to error log (safe for headers)
if (isset($_SESSION)) {
    error_log('SESSION: ' . print_r($_SESSION, true));
}
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_bulk_email'])) {
        $subject = $_POST['subject'];
        $message = $_POST['message'];
        $category = $_POST['category'];

        // Fetch users by category (role name)
        $stmt = $pdo->prepare("SELECT u.email FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = ?");
        $stmt->execute([$category]);
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($emails) > 0) {
            foreach ($emails as $email) {
                // Send email logic
                mail($email, $subject, $message, "From: admin@school.edu");
            }
            $_SESSION['success'] = "Emails sent successfully to all $category users!";
        } else {
            $_SESSION['error'] = "No users found in the selected category.";
        }
    }

    if (isset($_POST['import_emails'])) {
        if (!empty($_FILES['email_file']['tmp_name'])) {
            $subject = $_POST['subject_import'];
            $message = $_POST['message_import'];
            $file = fopen($_FILES['email_file']['tmp_name'], 'r');
            $sentCount = 0;
            $importedCount = 0;
            while (($line = fgetcsv($file)) !== false) {
                $email = trim($line[0]);
                $roleName = isset($line[1]) && !empty($line[1]) ? trim($line[1]) : 'parent';
                // Fetch role_id from roles table
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
                $roleStmt->execute([$roleName]);
                $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($role) {
                    $role_id = $role['id'];
                    $stmt = $pdo->prepare("INSERT IGNORE INTO users (email, role_id) VALUES (?, ?)");
                    if ($stmt->execute([$email, $role_id])) {
                        $importedCount++;
                    }
                    // Send email after import
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        mail($email, $subject, $message, "From: admin@school.edu");
                        $sentCount++;
                    }
                }
                // Optionally, handle emails with invalid roles (skip or log)
            }
            fclose($file);
            $_SESSION['success'] = "Imported $importedCount emails and sent $sentCount emails!";
        } else {
            $_SESSION['error'] = "Please upload a valid CSV file.";
        }
    }
}

// Fetch categories from the database
$categories = [];
$roleStmt = $pdo->query("SELECT role_name FROM roles ORDER BY role_name");
if ($roleStmt) {
    $categories = $roleStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Email - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
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
        .adugna-main-content {
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-row {
            display: flex;
            gap: 2.5rem;
            flex-wrap: wrap;
        }
        .adugna-col {
            flex: 1 1 350px;
            min-width: 320px;
            max-width: 520px;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header {
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
        .adugna-form-group select,
        .adugna-form-group textarea {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-group textarea {
            min-height: 100px;
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
        .adugna-alert-success {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        @media (max-width: 1100px) {
            .adugna-main-content { max-width: 99vw; margin: 18px 2vw 0 2vw; padding: 10px 4px 18px 4px; }
        }
        @media (max-width: 900px) {
            .adugna-row { flex-direction: column; gap: 1.5rem; }
            .adugna-col { max-width: 100%; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.5rem 0.2rem 0.7rem 0.2rem; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="adugna-main-content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="adugna-alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="adugna-alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <div class="adugna-row">
                    <div class="adugna-col">
                        <div class="adugna-card">
                            <div class="adugna-card-header">
                                <i class="fas fa-envelope"></i> Send Bulk Email
                            </div>
                            <form method="POST">
                                <div class="adugna-form-group">
                                    <label for="category">Select Category:</label>
                                    <select id="category" name="category" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category; ?>"><?php echo ucfirst($category); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adugna-form-group">
                                    <label for="subject">Subject:</label>
                                    <input type="text" id="subject" name="subject" required>
                                </div>
                                <div class="adugna-form-group">
                                    <label for="message">Message:</label>
                                    <textarea id="message" name="message" rows="5" required></textarea>
                                </div>
                                <button type="submit" name="send_bulk_email" class="adugna-btn">
                                    <i class="fas fa-paper-plane"></i> Send Email
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="adugna-col">
                        <div class="adugna-card">
                            <div class="adugna-card-header">
                                <i class="fas fa-upload"></i> Import Emails & Send
                            </div>
                            <p class="adugna-placeholder-hint" style="margin-bottom:1em;">
                                Download the <a href="bulk_email_template.php" class="adugna-btn adugna-btn-secondary adugna-btn-sm">CSV Template</a> and fill in your bulk email addresses.<br>
                                <strong>CSV Format:</strong> <code>email,role</code> (role can be admin, teacher, parent, or student; role is optional and defaults to parent)
                            </p>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="adugna-form-group">
                                    <label for="email_file">Upload CSV File:</label>
                                    <input type="file" id="email_file" name="email_file" accept=".csv" required>
                                </div>
                                <div class="adugna-form-group">
                                    <label for="subject_import">Subject:</label>
                                    <input type="text" id="subject_import" name="subject_import" required>
                                </div>
                                <div class="adugna-form-group">
                                    <label for="message_import">Message:</label>
                                    <textarea id="message_import" name="message_import" rows="5" required></textarea>
                                </div>
                                <button type="submit" name="import_emails" class="adugna-btn">
                                    <i class="fas fa-upload"></i> Import & Send Emails
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
