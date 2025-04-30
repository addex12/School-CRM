<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
 */
ob_start(); // Start output buffering
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Adugna Gizaw: Responsive, compact, ERPNext-inspired add user page with adugna- prefix -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/add_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/additional-methods.min.js"></script>
    <style>
        /**
         * Adugna Gizaw: Custom adugna- styles for compact, ERPNext-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         */
        html {
            font-size: 16px;
        }
        @media (max-width: 900px) {
            html { font-size: 15px; }
        }
        @media (max-width: 600px) {
            html { font-size: 14px; }
        }
        .adugna-form-grid {
            flex: 1 1 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.2rem;
            margin: 0 auto;
        }
        .adugna-card {
            width: 92%;
            max-width: 420px;
            padding: 1.1rem 1.3rem 1.2rem 1.3rem;
            box-shadow: 0 1px 4px rgba(25, 118, 210, 0.09);
            background: #fff;
            border-radius: 8px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card h2 {
            font-size: 1.13em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 0.5em;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .adugna-form-group {
            margin-bottom: 0.7em;
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
        .adugna-form-group select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
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
        .adugna-progress-bar-container {
            width: 100%;
            background: #f0f4fa;
            border-radius: 4px;
            margin-bottom: 0.5em;
            height: 22px;
            overflow: hidden;
            display: block;
        }
        .adugna-progress-bar {
            background: #1976d2;
            color: #fff;
            height: 100%;
            border-radius: 4px;
            text-align: center;
            font-size: 0.93em;
            transition: width 0.3s;
            line-height: 22px;
        }
        .adugna-error-message, .adugna-success-message {
            padding: 8px 12px;
            border-radius: 5px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        .adugna-error-message {
            background: #fbeaea;
            color: #e74c3c;
            border: 1px solid #f8d7da;
        }
        .adugna-success-message {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
        }
        @media (max-width: 768px) {
            .adugna-form-grid {
                flex: 1 1 100%;
            }
            .adugna-card {
                width: 100%;
                max-width: 99vw;
            }
        }
        @media (max-width: 480px) {
            .adugna-card {
                width: 98%;
                padding: 0.7rem 0.5rem 1rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Adugna Gizaw: Main admin dashboard layout -->
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
        <?= htmlspecialchars($pageTitle) ?>
        </header>
            <div class="admin-content" style="display: flex; justify-content: center; align-items: flex-start; min-height: 80vh; background: #f5f8ff; padding: 2.5rem 0;">
                <div class="adugna-form-grid" style="width: 100%; max-width: 900px; display: flex; flex-wrap: wrap; gap: 2.5rem; justify-content: center; align-items: flex-start;">
                    <div class="adugna-card" style="flex: 1 1 340px; min-width: 320px; max-width: 420px; margin: 0;">
                        <h2><i class="fas fa-user-plus" style="font-size:1em;"></i>Create Single User</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="adugna-form-group">
                                <label>Username</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="adugna-form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="adugna-form-group">
                                <label>User Role</label>
                                <select name="role_id" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>">
                                            <?= htmlspecialchars(ucfirst($role['role_name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="create_user" class="adugna-btn">
                                <i class="fas fa-plus"></i> Create User
                            </button>
                        </form>
                    </div>
                    <div class="adugna-card" style="flex: 1 1 340px; min-width: 320px; max-width: 420px; margin: 0;">
                        <h2><i class="fas fa-users" style="font-size:1em;"></i>Bulk Import Users</h2>
                        <div style="margin-bottom:1rem;">
                            <p>Download our CSV template to ensure proper formatting:</p>
                            <a href="download_template.php" class="adugna-btn adugna-btn-secondary">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                        </div>
                        <form method="POST" enctype="multipart/form-data" id="bulkImportForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="adugna-form-group">
                                <label>Upload CSV File</label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            </div>
                            <div class="adugna-progress-bar-container" id="progressContainer" style="display:none;">
                                <div class="adugna-progress-bar" id="progressBar" style="width:0%;">0%</div>
                            </div>
                            <button type="submit" name="bulk_import" class="adugna-btn" id="importBtn">
                                <i class="fas fa-upload"></i> Import Users
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include './includes/footer.php'; ?>
    <script>
        /**
         * Adugna Gizaw: AJAX bulk import progress bar logic for interactive feedback.
         */
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
                                var errorMsg = doc.querySelector('.adugna-error-message');
                                var successMsg = doc.querySelector('.adugna-success-message');
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
<?php ob_flush(); // Flush the output buffer ?>