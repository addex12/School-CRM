<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/setting.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_bulk_email'])) {
        $subject = $_POST['subject'];
        $message = $_POST['message'];
        $category = $_POST['category'];

        // Fetch users by category
        $stmt = $pdo->prepare("SELECT email FROM users WHERE role = ?");
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
            $file = fopen($_FILES['email_file']['tmp_name'], 'r');
            while (($line = fgetcsv($file)) !== false) {
                $email = $line[0];
                $role = $line[1] ?? 'parent'; // Default role if not provided
                $stmt = $pdo->prepare("INSERT IGNORE INTO users (email, role) VALUES (?, ?)");
                $stmt->execute([$email, $role]);
            }
            fclose($file);
            $_SESSION['success'] = "Emails imported successfully!";
        } else {
            $_SESSION['error'] = "Please upload a valid CSV file.";
        }
    }
}

// Fetch categories
$categories = ['admin', 'teacher', 'parent', 'student'];
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
                    <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="form-section">
                    <h2>Send Bulk Email</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="category">Select Category:</label>
                            <select id="category" name="category" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category; ?>"><?php echo ucfirst($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject:</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message:</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="send_bulk_email" class="btn btn-primary">Send Email</button>
                    </form>
                </div>

                <div class="form-section">
                    <h2>Import Emails</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="email_file">Upload CSV File:</label>
                            <input type="file" id="email_file" name="email_file" accept=".csv" required>
                        </div>
                        <button type="submit" name="import_emails" class="btn btn-primary">Import Emails</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
