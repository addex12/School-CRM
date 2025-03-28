<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get User Data
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: users.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found";
    header("Location: users.php");
    exit();
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

    try {
        // Check duplicates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users 
                             WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username or email already exists");
        }

        // Update user
        $stmt = $pdo->prepare("UPDATE users 
                             SET username = ?, email = ?, role_id = ?
                             WHERE id = ?");
        $stmt->execute([$username, $email, $role_id, $user_id]);
        
        $_SESSION['success'] = "User updated successfully";
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
    <title>Edit User - Admin Panel</title>
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
        
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto; /* Center align the form */
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
            <h1>Edit User</h1>
            
            <div class="form-container"> <!-- Changed class name from 'card' to 'form-container' -->
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
                        value="<?= htmlspecialchars($user['username']) ?>"
                        required
                    ></sl-input>
                    
                    <sl-input 
                        type="email" 
                        name="email" 
                        label="Email"
                        value="<?= htmlspecialchars($user['email']) ?>"
                        required
                    ></sl-input>
                    
                    <sl-select name="role_id" label="Role" value="<?= $user['role_id'] ?>">
                        <?php foreach ($roles as $role): ?>
                            <sl-option value="<?= $role['id'] ?>">
                                <?= htmlspecialchars($role['role_name']) ?>
                            </sl-option>
                        <?php endforeach; ?>
                    </sl-select>
                    
                    <div class="form-actions">
                        <sl-button type="submit" variant="primary">Update User</sl-button>
                        <sl-button href="users.php" variant="neutral">Cancel</sl-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.0.0-beta.25/dist/shoelace/shoelace.esm.js"></script>
</body>
</html>