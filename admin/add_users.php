<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: users.php");
        exit();
    }

    $username = htmlspecialchars($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $role_id = (int)$_POST['role_id'];
    $temp_password = bin2hex(random_bytes(16));

    try {
        // Check duplicates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username or email already exists");
        }

        // Create user
        $stmt = $pdo->prepare("INSERT INTO users 
                             (username, email, role_id, password)
                             VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $username,
            $email,
            $role_id,
            password_hash($temp_password, PASSWORD_DEFAULT)
        ]);
        
        $_SESSION['success'] = "User created successfully";
        header("Location: users.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get Roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.0.0-beta.25/dist/shoelace/shoelace.css">
    <style>
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .main-content {
            padding: 2rem;
            background: #f5f7fa;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-grid {
            display: grid;
            gap: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <h1>Add New User</h1>
            
            <div class="card">
                <?php if (isset($_SESSION['error'])): ?>
                    <sl-alert variant="danger" open>
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']) ?>
                    </sl-alert>
                <?php endif; ?>

                <form method="POST" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <sl-input 
                        name="username" 
                        label="Username"
                        required
                    ></sl-input>
                    
                    <sl-input 
                        type="email" 
                        name="email" 
                        label="Email"
                        required
                    ></sl-input>
                    
                    <sl-select name="role_id" label="Role">
                        <?php foreach ($roles as $role): ?>
                            <sl-option value="<?= $role['id'] ?>">
                                <?= htmlspecialchars($role['role_name']) ?>
                            </sl-option>
                        <?php endforeach; ?>
                    </sl-select>
                    
                    <div class="form-actions">
                        <sl-button type="submit" variant="primary">Create User</sl-button>
                        <sl-button href="users.php" variant="neutral">Cancel</sl-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.0.0-beta.25/dist/shoelace/shoelace.esm.js"></script>
</body>
</html>