<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Add User";

// Reconnect to MySQL if "server has gone away"
function ensurePdoConnection($pdo)
{
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

// Get roles with IDs (fix: fetch as associative array)
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);

// For AJAX polling progress
if (isset($_GET['bulk_progress'])) {
    $progressFile = sys_get_temp_dir() . '../assets/js/bulk_import_progress.json';
    if (file_exists($progressFile)) {
        header('Content-Type: application/json');
        echo file_get_contents($progressFile);
    } else {
        echo json_encode(['percent' => 0, 'status' => 'Starting...']);
    }
    exit;
}

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

            $progressFile = sys_get_temp_dir() . '../assets/js/bulk_import_progress.json';
            $totalRows = 0;
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            if (!$handle) {
                throw new Exception("Failed to open uploaded file");
            }
            // Count total rows for progress
            while (fgetcsv($handle)) $totalRows++;
            rewind($handle);

            // Skip header
            fgetcsv($handle);
            $rowNumber = 1;
            $errors = [];
            $imported = 0;
            $currentRow = 0;

            // Write initial progress
            file_put_contents($progressFile, json_encode(['percent' => 0, 'status' => 'Starting...']));

            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $currentRow++;
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

                // Get role ID from role name (case-insensitive)
                $stmt = $pdo->prepare("SELECT id FROM roles WHERE LOWER(role_name) = LOWER(?)");
                $stmt->execute([$roleName]);
                $role = $stmt->fetch(PDO::FETCH_ASSOC);

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
                $user_id = $pdo->lastInsertId();
                $imported++;

                // Assign to respective table based on role
                if (strtolower($roleName) === 'student') {
                    $pdo->prepare("INSERT INTO students (user_id, status) VALUES (?, 'active')")->execute([$user_id]);
                } elseif (strtolower($roleName) === 'teacher') {
                    $pdo->prepare("INSERT INTO teachers (user_id, status) VALUES (?, 'active')")->execute([$user_id]);
                } elseif (strtolower($roleName) === 'parent') {
                    $pdo->prepare("INSERT INTO parents (user_id) VALUES (?)")->execute([$user_id]);
                }

                // Send email (optional)
                $to = $email;
                $subject = "Your New Account";
                $message = "Dear School CRM Family! As per your request, following are your temporary credentials.\nUsername: $username\nTemporary Password: $temp_password";
                $headers = "From: adugna.gizaw@flipperschools.com";
                @mail($to, $subject, $message, $headers);

                // Update progress
                $percent = $totalRows > 0 ? intval(($currentRow / $totalRows) * 100) : 100;
                file_put_contents($progressFile, json_encode([
                    'percent' => $percent,
                    'status' => "Imported $currentRow of $totalRows"
                ]));
            }

            fclose($handle);

            // Final progress
            file_put_contents($progressFile, json_encode([
                'percent' => 100,
                'status' => "Done. Imported $imported users." . (empty($errors) ? "" : " Some rows had errors.")
            ]));

            if (!empty($errors)) {
                $_SESSION['bulk_import_errors'] = $errors;
            }
            $_SESSION['success'] = "Bulk import completed. $imported users imported." . (empty($errors) ? "" : " Some rows had errors.");

            // Remove progress file after a short delay (let JS poll one last time)
            register_shutdown_function(function () use ($progressFile) {
                sleep(3);
                @unlink($progressFile);
            });

            header("Location: add_users.php");
            exit();
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

            // Get role name for assignment
            $roleName = '';
            foreach ($roles as $role) {
                if ($role['id'] == $role_id) {
                    $roleName = strtolower($role['role_name']);
                    break;
                }
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
            $user_id = $pdo->lastInsertId();

            // Assign to respective table based on role
            if ($roleName === 'student') {
                $pdo->prepare("INSERT INTO students (user_id, status) VALUES (?, 'active')")->execute([$user_id]);
            } elseif ($roleName === 'teacher') {
                $pdo->prepare("INSERT INTO teachers (user_id, status) VALUES (?, 'active')")->execute([$user_id]);
            } elseif ($roleName === 'parent') {
                $pdo->prepare("INSERT INTO parents (user_id) VALUES (?)")->execute([$user_id]);
            }

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
    <link rel="stylesheet" href="../assets/css/add_users.css">
</head>

<body>
    <div class="admin-dashboard">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <?= htmlspecialchars($pageTitle) ?>
            </header>

            <div class="admin-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="erpnext-error-message">
                        <?= htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['bulk_import_errors'])): ?>
                    <div class="erpnext-error-message">
                        <h3>Some rows were skipped due to errors:</h3>
                        <ul>
                            <?php foreach ($_SESSION['bulk_import_errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php unset($_SESSION['bulk_import_errors']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="erpnext-success-message">
                        <?= htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="erpnext-form-grid">
                    <!-- Single User Form -->
                    <div class="erpnext-card">
                        <h2>Create Single User</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <div class="erpnext-form-group">
                                <label>Username</label>
                                <input type="text" name="username" required>
                            </div>

                            <div class="erpnext-form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required>
                            </div>

                            <div class="erpnext-form-group">
                                <label>User Role</label>
                                <select name="role_id" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>">
                                            <?= htmlspecialchars(ucfirst($role['role_name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" name="create_user" class="erpnext-btn erpnext-btn-primary">
                                Create User
                            </button>
                        </form>
                    </div>

                    <!-- Bulk Import -->
                    <div class="erpnext-card">
                        <h2>Bulk Import Users</h2>
                        <div style="margin-bottom:1rem;">
                            <p>Download our CSV template to ensure proper formatting:</p>
                            <a href="download_template.php" class="erpnext-btn erpnext-btn-secondary">
                                Download Template
                            </a>
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="bulkImportForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="erpnext-form-group">
                                <label>Upload CSV File</label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            </div>
                            <div class="erpnext-progress-bar-container" id="progressContainer" style="display:none;">
                                <div class="erpnext-progress-bar" id="progressBar" style="width:0%;">0%</div>
                            </div>
                            <button type="submit" name="bulk_import" class="erpnext-btn erpnext-btn-primary" id="importBtn">
                                Import Users
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include './includes/footer.php'; ?>
    <script>
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

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            progressBar.style.width = '100%';
                            if (xhr.status === 200) {
                                var parser = new DOMParser();
                                var doc = parser.parseFromString(xhr.responseText, 'text/html');
                                var errorMsg = doc.querySelector('.erpnext-error-message');
                                var successMsg = doc.querySelector('.erpnext-success-message');
                                if (errorMsg) {
                                    progressBar.style.background = '#e74c3c';
                                    progressBar.textContent = errorMsg.textContent.trim();
                                } else if (successMsg) {
                                    progressBar.style.background = '#27ae60';
                                    progressBar.textContent = successMsg.textContent.trim();
                                } else {
                                    progressBar.textContent = 'Done';
                                }
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1800);
                            } else {
                                progressBar.style.background = '#e74c3c';
                                progressBar.textContent = 'Upload failed';
                                importBtn.disabled = false;
                            }
                        }
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
    <script src="../assets/js/bulk_import_progress.js"></script>
</body>
</html>