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
            throw new Exception("Your session has expired or the request is invalid. Please try again.");
        }

        // Single user creation (unchanged)
        // ... [existing single user code] ...

        // Bulk import - FIXED VERSION
        if (isset($_POST['bulk_import'])) {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Failed to upload file");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            if (!$handle) {
                throw new Exception("Failed to open uploaded file");
            }

            $pdo->beginTransaction();
            try {
                // Skip header
                fgetcsv($handle);

                $rowNumber = 1; // Start counting from header row
                $errors = [];

                while (($data = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    $username = trim($data[0] ?? '');
                    $email = trim($data[1] ?? '');
                    $roleName = trim($data[2] ?? ''); // Now using role name

                    // Validate required fields
                    if (empty($username) || empty($email) || empty($roleName)) {
                        $errors[] = "Row $rowNumber: Missing required fields (username, email, or role)";
                        continue;
                    }

                    // Validate email format
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Row $rowNumber: Invalid email format";
                        continue;
                    }

                    // Get role ID from role name
                    $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
                    $stmt->execute([$roleName]);
                    $role = $stmt->fetch();
                    
                    if (!$role) {
                        $errors[] = "Row $rowNumber: Role '$roleName' does not exist";
                        continue;
                    }
                    $role_id = (int)$role['id'];

                    // Check for existing users
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = "Row $rowNumber: Username or email already exists";
                        continue;
                    }

                    // Create user
                    $temp_password = bin2hex(random_bytes(8));
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, role_id, password) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $username,
                        $email,
                        $role_id,
                        password_hash($temp_password, PASSWORD_DEFAULT)
                    ]);

                    // Send email (optional)
                    $to = $email;
                    $subject = "Your New Account";
                    $message = "Username: $username\nTemporary Password: $temp_password";
                    $headers = "From: no-reply@example.com";
                    @mail($to, $subject, $message, $headers);
                }

                $pdo->commit();

                if (!empty($errors)) {
                    $_SESSION['bulk_import_errors'] = $errors;
                } else {
                    $_SESSION['success'] = "Bulk import completed successfully!";
                }

                header("Location: add_users.php");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Bulk import failed: " . $e->getMessage();
            } finally {
                fclose($handle);
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: add_users.php");
        exit();
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

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
            overflow: hidden; /* Prevent content overflow */
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Ensure two equal columns */
            gap: 2rem;
            margin-top: 2rem;
            align-items: start; /* Align items at the start for consistent alignment */
        }

        .admin-main {
            margin-left: 250px; /* Adjust to ensure it doesn't overlap the sidebar */
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

        .admin-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937; /* Consistent dark gray color */
            margin-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb; /* Add a subtle underline */
            padding-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1> <!-- Ensure consistent styling -->
            </header>

            <div class="admin-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['bulk_import_errors'])): ?>
                    <div class="error-message">
                        <h3>Some rows were skipped due to errors:</h3>
                        <ul>
                            <?php foreach ($_SESSION['bulk_import_errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php unset($_SESSION['bulk_import_errors']); ?>
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