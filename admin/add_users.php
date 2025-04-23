<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Add User";

// Reconnect to MySQL if "server has gone away"
function ensurePdoConnection($pdo) {
    try {
        $pdo->query('SELECT 1');
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'server has gone away') !== false) {
            // Reconnect logic
            require_once '../includes/config.php';
            return $pdo; // $pdo is re-initialized in config.php
        } else {
            throw $e;
        }
    }
    return $pdo;
}

// Ensure connection before any queries
$pdo = ensurePdoConnection($pdo);

// Get roles with IDs
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Your session has expired or the request is invalid. Please try again.");
        }

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
                    $message = "Dear School CRM Family! As per your request, following are your temporary credentials.\nUsername: $username\nTemporary Password: $temp_password";
                    $headers = "From: adugna.gizaw@flipperschools.com";
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

        if (isset($_POST['create_user'])) {
            $username = trim($_POST['username']); // Ensure $username is defined
            $email = trim($_POST['email']);
            $role_id = (int)$_POST['role_id'];

            // Validate required fields
            if (empty($username) || empty($email) || empty($role_id)) {
                throw new Exception("All fields are required.");
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format.");
            }

            // Check for existing users
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username or email already exists.");
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
            $message = "Welcome to School CRM! Your Account has been created successfully! \n Following are your new credentioals\nUsername: $username\nTemporary Password: $temp_password";
            $headers = "From: adugna.gizaw@flipperschools.com";
            @mail($to, $subject, $message, $headers);

            // Display success message
            $_SESSION['success'] = "Account for '$username' created successfully!";
            header("Location: add_users.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: add_users.php");
        exit();
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));


// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
        .success-message {
    background: #dcfce7;
    color: #16a34a;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.admin-header h1 {
    font-size: 2rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
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

        .erpnext-btn, .btn, .btn-primary, .btn-secondary {
            display: inline-block;
            padding: 10px 22px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #222d32;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            margin-right: 4px;
        }
        .erpnext-btn:hover, .btn:hover, .btn-primary:hover, .btn-secondary:hover {
            background: #e2efda;
            color: #215967;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #eaeaea;
            color: #666;
        }
        .progress-bar-container {
            width: 100%;
            background: #f3f4f6;
            border-radius: 6px;
            margin: 1rem 0;
            height: 28px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
        }
        .progress-bar {
            height: 100%;
            background: #3b82f6;
            color: #fff;
            font-weight: 600;
            text-align: center;
            line-height: 28px;
            border-radius: 6px 0 0 6px;
            transition: width 0.3s;
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
                <?php if (isset($_SESSION['error'])): ?>
    <div class="error-message">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="success-message">
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
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
                            <?php if (isset($_SESSION['error'])): ?>
    <div class="error-message">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="success-message">
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

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
                            <a href="download_template.php" class="erpnext-btn btn-secondary">
                                Download Template
                            </a>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="mt-4" id="bulkImportForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="form-group">
                                <label>Upload CSV File</label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            </div>
                            <div class="progress-bar-container" id="progressContainer" style="display:none;">
                                <div class="progress-bar" id="progressBar" style="width:0%;">0%</div>
                            </div>
                            <button type="submit" name="bulk_import" class="erpnext-btn btn-primary" id="importBtn">
                                Import Users
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
// Progress bar for bulk import (client-side simulation)
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('bulkImportForm');
    var progressContainer = document.getElementById('progressContainer');
    var progressBar = document.getElementById('progressBar');
    var importBtn = document.getElementById('importBtn');
    var csvInput = document.getElementById('csv_file');

    if (form && progressContainer && progressBar && importBtn && csvInput) {
        form.addEventListener('submit', function(e) {
            if (!csvInput.files.length) return;
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            importBtn.disabled = true;

            // Use AJAX for real upload and progress
            var file = csvInput.files[0];
            var formData = new FormData();
            formData.append('csv_file', file);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
            formData.append('bulk_import', '1');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_users.php', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                }
            };

            xhr.onload = function() {
                progressBar.style.width = '100%';
                progressBar.textContent = 'Processing...';
                setTimeout(function() {
                    window.location.reload();
                }, 800);
            };

            xhr.onerror = function() {
                progressBar.style.background = '#e74c3c';
                progressBar.textContent = 'Upload failed';
                importBtn.disabled = false;
            };

            xhr.send(formData);
            e.preventDefault();
        });
    }
});
</script>
</body>
</html>