<?php
// File: add_user.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Add User";

// Get roles
$roles = $pdo->query("SELECT role_name FROM roles ORDER BY role_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Single user creation
        if (isset($_POST['create_user'])) {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $role = trim($_POST['role']);
            $temp_password = bin2hex(random_bytes(8));

            // Validation
            if (empty($username) || empty($email) || empty($role)) {
                throw new Exception("All fields are required");
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $username,
                $email,
                $role,
                password_hash($temp_password, PASSWORD_DEFAULT)
            ]);

            // Send email
            $to = $email;
            $subject = "Your New Account";
            $message = "Username: $username\nTemporary Password: $temp_password";
            $headers = "From: no-reply@example.com";
            
            if (!mail($to, $subject, $message, $headers)) {
                throw new Exception("Failed to send email");
            }

            $_SESSION['success'] = "User created successfully. Credentials emailed.";
            header("Location: users.php");
            exit();
        }

        // Bulk import
        if (isset($_POST['bulk_import'])) {
            // ... [Bulk import code] ...
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Add New User</h1>
            </header>
            
            <div class="content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <h2>Create Single User</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Role:</label>
                            <select name="role" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= htmlspecialchars($role['role_name']) ?>">
                                        <?= ucfirst(htmlspecialchars($role['role_name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                    </form>
                </div>

                <div class="card mt-4">
                    <h2>Bulk Import Users</h2>
                    <a href="download_template.php" class="btn btn-secondary">Download CSV Template</a>
                    
                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <div class="form-group">
                            <input type="file" name="csv_file" accept=".csv" required>
                        </div>
                        <button type="submit" name="bulk_import" class="btn btn-primary">Import Users</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>