<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-dashboard">
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="admin-main">
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card form-section">
                        <div class="card-header">
                            <h2 class="section-title">Send Bulk Email</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="category">Select Category:</label>
                                    <select id="category" name="category" class="form-control" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category; ?>"><?php echo ucfirst($category); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="subject">Subject:</label>
                                    <input type="text" id="subject" name="subject" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="message">Message:</label>
                                    <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" name="send_bulk_email" class="btn btn-primary btn-block">Send Email</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card form-section">
                        <div class="card-header">
                            <h2 class="section-title">Import Emails & Send</h2>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                Download the <a href="bulk_email_template.php" class="btn btn-secondary btn-sm">CSV Template</a> and fill in your bulk email addresses.<br>
                                <strong>CSV Format:</strong> <code>email,role</code> (role can be admin, teacher, parent, or student; role is optional and defaults to parent)
                            </p>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="email_file">Upload CSV File:</label>
                                    <input type="file" id="email_file" name="email_file" class="form-control" accept=".csv" required>
                                </div>
                                <div class="form-group">
                                    <label for="subject_import">Subject:</label>
                                    <input type="text" id="subject_import" name="subject_import" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="message_import">Message:</label>
                                    <textarea id="message_import" name="message_import" class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" name="import_emails" class="btn btn-primary btn-block">Import & Send Emails</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
