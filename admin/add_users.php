<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Add User";

// Get roles with IDs
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }

        // Single user creation
        if (isset($_POST['create_user'])) {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $role_id = (int)$_POST['role_id'];
            $temp_password = bin2hex(random_bytes(8));

            // Validation
            if (empty($username) || empty($email) || empty($role_id)) {
                throw new Exception("All fields are required");
            }

            // Check for existing username or email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username or email already exists");
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, email, role_id, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $username,
                $email,
                $role_id,
                password_hash($temp_password, PASSWORD_DEFAULT)
            ]);

            // Send email (configure your mail server properly)
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
            // ... [Bulk import code with similar fixes] ...
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .admin-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
            align-items: start; /* Align items at the start for consistent alignment */
        }

        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            display: flex;
            flex-direction: column; /* Ensure content stacks properly */
            justify-content: space-between; /* Space out content evenly */
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            background: #f9fafb;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }

        .file-upload {
            border: 2px dashed #e5e7eb;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1>Add New User</h1>
            </header>

            <div class="admin-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="form-grid">
                    <!-- Single User Form -->
                    <div class="card">
                        <h2>Create Single User</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label>User Role</label>
                                <select name="role_id" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>">
                                            <?= htmlspecialchars(ucfirst($role['role_name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="create_user" class="btn btn-primary">
                                Create User
                            </button>
                        </form>
                    </div>

                    <!-- Bulk Import -->
                    <div class="card">
                        <h2>Bulk Import Users</h2>
                        <div class="file-upload">
                            <p>Download our CSV template to ensure proper formatting:</p>
                            <a href="download_template.php" class="btn btn-secondary">
                                Download Template
                            </a>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="form-group">
                                <label>Upload CSV File</label>
                                <input type="file" name="csv_file" accept=".csv" required>
                            </div>
                            
                            <button type="submit" name="bulk_import" class="btn btn-primary">
                                Import Users
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>